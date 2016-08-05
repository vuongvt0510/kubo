module Capistrano
  module AWS
    module Configuration

      class ElasticBeanstalkApplication

        attr_accessor :name, :type, :bucket, :environments, :extensions

        def initialize(name, options = {})
          @name = name
          @type = type
          @bucket = options[:bucket] || nil
          @extensions = options[:extensions] || {}
          @environments = []
          (options[:environments] || []).each do |e|
            @environments << ElasticBeanstalkEnvironment.new(e)
          end
        end

        def with(options)
          @name = name
          @type = type
          @bucket = options[:bucket] || nil
          @extensions = options[:extensions] || {}
          @environments = []

          (options[:environments] || []).each do |e|
            @environments << ElasticBeanstalkEnvironment.new(e)
          end
        end

        def matches?(other)
          application_name == other.application_name
        end
      end

      class ElasticBeanstalkEnvironment
        attr_accessor :name, :version

        def initialize(name, options = {})
          @name = name
        end
      end

      class ElasticBeanstalks
        include Enumerable

        def add_application(application_name, options = {})
          newer = ElasticBeanstalkApplication.new(application_name, options)

          if application = applications.find {|a| a.matches? newer}
            application.with(options)
          else
            applications << newer
          end
        end

        def each
          applications.each {|application| yield application}
        end

        private
        def applications
          @applications ||= []
        end
      end

    end
  end
end

