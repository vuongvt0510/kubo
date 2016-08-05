require 'capistrano/git'
require 'json'

module Capistrano
  module AWS

    module ElasticBeanstalk

      class Git < ::Capistrano::Git

        module DefaultStrategy
          include Capistrano::Git::DefaultStrategy

          def test
            test! " [ -f #{aws_elasticbeanstalk_repo_path}/HEAD ] "
          end

          def clone
            git :clone, '--mirror', repo_url, aws_elasticbeanstalk_repo_path
          end

          def release(application)

            context.execute :mkdir, "-p", archive = aws_elasticbeanstalk_archive_path(application)

            if tree = fetch(:repo_tree)
              tree = tree.slice %r#^/?(.*?)/?$#, 1
              components = tree.split('/').size
              git :archive, fetch(:branch), tree, "| tar -x --strip-components #{components} -f - -C", archive
            else
              git :archive, fetch(:branch), '| tar -x -f - -C', archive
            end
          end

        end

      end
    end
  end
end

