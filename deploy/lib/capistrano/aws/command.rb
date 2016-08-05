require 'forwardable'

module Capistrano
  module AWS
    class Command
      extend Forwardable

      attr_reader :context

      def_delegators :@context, :fetch

      def initialize(context, strategy)
        @context = context
        singleton = class << self; self; end
        singleton.send(:include, strategy)
      end

      def check
        context.execute "which aws"
      end

      def execute(*args)
        args.unshift :aws
        args.unshift(*aws_env)
        context.execute(*args)
      end

      def capture(*args)
        args.unshift :aws
        args.unshift(*aws_env)
        context.capture(*args)
      end

      module DefaultStrategy
      end

      private
      def aws_env
        env = [
          "AWS_DEFAULT_REGION=#{fetch(:aws_region, 'ap-northeast-1')}"
        ]

        if fetch(:aws_access_key_id) and fetch(:aws_secret_access_key)
          env.push "AWS_ACCESS_KEY_ID=#{fetch(:aws_access_key_id)}"
          env.push "AWS_SECRET_ACCESS_KEY=#{fetch(:aws_secret_access_key)}"
        end

        env
      end
    end
  end
end

