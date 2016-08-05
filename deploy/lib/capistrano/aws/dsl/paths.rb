require 'tmpdir'

module Capistrano
  module DSL
    module Paths

      def aws_elasticbeanstalk_repo_path
        File.join(Dir.tmpdir, fetch(:application))
      end

      def aws_elasticbeanstalk_archive_path(application)
        application_name = application.is_a?(Capistrano::AWS::Configuration::ElasticBeanstalkApplication) ? application.name : application
        dirname = application_name.to_s + "-" + fetch(:branch, :master).to_s.gsub("/", "-") + "-" + now
        File.join(Dir.tmpdir, dirname)
      end

      def aws_elasticbeanstalk_archive_file(application)
        aws_elasticbeanstalk_archive_path(application) + ".zip"
      end

    end
  end
end

