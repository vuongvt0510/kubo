module Capistrano
  module AWS
    module DSL
      module Env
        def aws_region(region)
          env.set(:aws_region, region)
        end

        def aws_access_key_id(id)
          env.set(:aws_access_key_id, id)
        end

        def aws_secret_access_key(key)
          env.set(:aws_secret_access_key, key)
        end

        def aws_elasticbeanstalk(application_name, options = {})
          env.add_aws_elasticbeanstalk(application_name, options)
        end
      end
    end
  end
end

