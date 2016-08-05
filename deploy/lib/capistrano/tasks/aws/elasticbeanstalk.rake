require File.expand_path('../../../aws/dsl.rb', __FILE__)
require File.expand_path('../../../aws/command.rb', __FILE__)
require File.expand_path('../../../aws/elasticbeanstalk/git.rb', __FILE__)
require 'erb'

namespace :aws do

  def aws(*args)
    @aws ||= Capistrano::AWS::Command.new(self, Capistrano::AWS::Command::DefaultStrategy)
  end

  namespace :elasticbeanstalk do

    desc "Deploy a new release by AWS Elastic Beanstalk"
    task :deploy do
      invoke "aws:elasticbeanstalk:deploy:starting"
      invoke "aws:elasticbeanstalk:deploy:started"
      invoke "aws:elasticbeanstalk:deploy:uploading"
      invoke "aws:elasticbeanstalk:deploy:uploaded"
      invoke "aws:elasticbeanstalk:deploy:publishing"
      invoke "aws:elasticbeanstalk:deploy:published"
      invoke "aws:elasticbeanstalk:deploy:finishing"
      invoke "aws:elasticbeanstalk:deploy:finished"
    end

    namespace :deploy do
      desc "Start a deployment, make sure ready"
      task :starting do
        invoke "aws:elasticbeanstalk:#{scm}:check"
        invoke "aws:elasticbeanstalk:deploy:check"
      end

      desc "Started"
      task :started do
      end

      desc "Create a new release to AWS S3 bucket with ebextensions, And attach to AWS Elastic Beanstalk"
      task :uploading do
        invoke "aws:elasticbeanstalk:#{scm}:update"
        invoke "aws:elasticbeanstalk:#{scm}:set_current_revision"

        env.aws_elasticbeanstalk_applications.each do |ap|
          Rake::Task["aws:elasticbeanstalk:#{scm}:create_release"].execute application: ap
          Rake::Task["aws:elasticbeanstalk:deploy:create_extensions"].execute application: ap
          Rake::Task["aws:elasticbeanstalk:deploy:upload_release"].execute application: ap
        end
      end

      desc "Uploaded"
      task :uploaded do
      end

      desc "Update AWS Elastic Beanstalk environment version label"
      task :publishing do
        env.aws_elasticbeanstalk_applications.each do |ap|
          Rake::Task["aws:elasticbeanstalk:deploy:publish_release"].execute application: ap
        end
      end

      desc "Published"
      task :published do
      end

      desc "Wait publishing AWS Elastic Beanstalk"
      task :finishing do
        invoke "aws:elasticbeanstalk:deploy:waiting_completion"
      end

      desc "Finished"
      task :finished do
      end

      desc "Check AWS Elastic Beanstalk applications exist and environments exist"
      task :check do
        invoke "aws:elasticbeanstalk:deploy:check:command"
        invoke "aws:elasticbeanstalk:deploy:check:applications"
      end

      namespace :check do
        desc "Check AWS Elastic Beanstalk applications exist"
        task :applications do
          env.aws_elasticbeanstalk_applications.each do |app|
            app.environments.each do |e|
              run_locally do 
                r = JSON.parse(aws.capture :elasticbeanstalk, 'describe-environments', '--environment-name', e.name)
                if r['Environments'].empty?
                  raise "#{app.name} #{e.name} is not found in AWS Elastic Beanstalk"
                end

                unless r['Environments'][0]['Status'] == 'Ready'
                  raise "#{app.name} #{e.name} status is not ready"
                end

                e.version = r['Environments'][0]['VersionLabel']
              end
            end
          end
        end

        desc "Check AWS CLI installed"
        task :command do
          run_locally do
            aws.check
          end
        end
      end

      desc "Create AWS Elastic Beanstalk extension directory to a new release"
      task :create_extensions do |name, options|
        run_locally do
          within archive_dir = aws_elasticbeanstalk_archive_path(options[:application]) do
            template_dir = File.join(aws_elasticbeanstalk_archive_path(options[:application]), "deploy", "lib", "capistrano", "templates", "aws", "elasticbeanstalk")

            if test "[ -d #{template_dir} ]"
              execute :rm, "-rf .ebextensions"
              execute :mkdir, ".ebextensions"

              extensions = [
                :core,
                :option,
                :"after-publishing"
              ].push(*options[:application].extensions.keys).uniq

              extensions.each do |ex|

                file = ex.to_s + ".config"

                template_file = capture(:ls, File.join(template_dir, "*#{file}*"), "|| true").chomp

                if template_file.empty?
                  warn("AWS ElasticBeanstalk config file (#{file}) is not found.")
                else
                  if File.extname(template_file) == '.erb'
                    basename = File.basename(template_file, ".config.erb")
                    config_file = File.join(archive_dir, ".ebextensions", "#{basename}.config")
                    script_file = File.join(archive_dir, ".ebextensions", "#{basename}.config.rb")

                    json = {
                      repo_path: aws_elasticbeanstalk_archive_path(options[:application]),
                      current_path: "/var/www/html",
                      application: fetch(:application),
                      stage: fetch(:stage),
                      type: options[:application].type,
                      extensions: options[:application].extensions
                    }

                    json[ex] = options[:application].extensions.has_key?(ex) ? options[:application].extensions[ex] : {}

                    json = JSON.generate(json)

                    execute :erb, "-x #{template_file} > #{script_file}"
                    execute :echo, "'print _erbout' >> #{script_file}"
                    execute :ruby,"#{script_file} '#{json}' > #{config_file}"
                    execute :rm,  script_file
                    execute :cat, config_file

                  elsif File.extname(template_file) == '.config'
                    config_file = File.join(archive_dir, ".ebextensions", "#{basename}.config")
                    execute :cp, template_file, config_file
                    execute :cat, config_file
                  end
                end
              end
            end
          end
        end
      end

      desc "Upload a new release to AWS S3"
      task :upload_release do |name, options|
        run_locally do
          begin
            version_label = fetch(:elasticbeanstalk_current_version_label)

            versions = aws.capture :elasticbeanstalk, "describe-application-versions --application-name=#{options[:application].name} --version-labels=#{version_label}"
            versions = JSON.parse(versions)

            unless versions['ApplicationVersions'].empty?
              info "version #{version_label} was already uploaded."
            else
              dir = aws_elasticbeanstalk_archive_path(options[:application])
              file = aws_elasticbeanstalk_archive_file(options[:application])
              bucket = options[:application].bucket
              key = File.basename(file)
              s3_url = "s3://#{bucket}/#{key}"

              within dir do
                execute :zip, "-r", file, "."
              end

              aws.execute :s3, "cp #{file} #{s3_url}"
              aws.execute :elasticbeanstalk, "create-application-version --output json --application-name #{options[:application].name} --version-label #{version_label} --source-bundle S3Bucket=#{bucket},S3Key=#{key}"
            end

          ensure
            execute :rm, "-rf", dir, file
          end
        end
      end

      desc "Update AWS Elastic Beanstalk version label"
      task :publish_release do |name, options|
        version = fetch(:elasticbeanstalk_current_version_label)

        latest_events = {}

        options[:application].environments.each do |e|
          run_locally do 
            result = aws.capture :elasticbeanstalk, "describe-events --environment-name #{e.name} --max-item 1" 
            result = JSON.parse(result)
            latest_events[e.name] = result['Events'].empty? ? [] : result['Events'][0]

            aws.execute :elasticbeanstalk, "update-environment --environment-name #{e.name} --version-label #{version}"
          end
        end

        set :elasticbeanstalk_latest_events, latest_events
      end

      desc "Wait AWS Elastic Beanstalk publishing"
      task :waiting_completion do |name, options|
      end
    end

    namespace :git do
      task :check do
        set :git_strategy, Capistrano::AWS::ElasticBeanstalk::Git::DefaultStrategy

        run_locally do
          strategy.check
        end
      end

      task :clone do
        set :git_strategy, Capistrano::AWS::ElasticBeanstalk::Git::DefaultStrategy

        run_locally do
          if strategy.test
            info t(:mirror_exists, at: aws_elasticbeanstalk_repo_path)
          else
            strategy.clone
          end
        end
      end

      task update: :"aws:elasticbeanstalk:git:clone" do
        set :git_strategy, Capistrano::AWS::ElasticBeanstalk::Git::DefaultStrategy

        run_locally do
          within aws_elasticbeanstalk_repo_path do
            strategy.update
          end
        end
      end

      task create_release: :"aws:elasticbeanstalk:git:update" do |name, options|
        set :git_strategy, Capistrano::AWS::ElasticBeanstalk::Git::DefaultStrategy

        run_locally do
          within aws_elasticbeanstalk_repo_path do
            strategy.release(options[:application])
          end
        end

      end

      task :set_current_revision do
        set :git_strategy, Capistrano::AWS::ElasticBeanstalk::Git::DefaultStrategy

        run_locally do
          within aws_elasticbeanstalk_repo_path do
            set :current_revision, strategy.fetch_revision
            set :elasticbeanstalk_current_version_label, (fetch(:branch, :master).to_s.gsub("/", "-") + "-" + fetch(:current_revision)).chomp
          end
        end
      end
    end

  end
end

