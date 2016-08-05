require File.expand_path('../configuration.rb', __FILE__)
require File.expand_path('../dsl/paths.rb', __FILE__)
require File.expand_path('../dsl/env.rb', __FILE__)

module Capistrano
  module AWS
    module DSL
      include Env
    end
  end
end

self.extend Capistrano::AWS::DSL

