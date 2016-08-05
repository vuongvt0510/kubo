require_relative 'configuration/elasticbeanstalk.rb'

module Capistrano
  module AWS
    module Configuration
      def add_aws_elasticbeanstalk(application_name, options)
        aws_elasticbeanstalk_applications.add_application(application_name, options)
      end

      def aws_elasticbeanstalk_applications
        @aws_elasticbeanstalk_applications ||= ElasticBeanstalks.new
      end
    end
  end

  class Configuration
    include AWS::Configuration
  end
end

