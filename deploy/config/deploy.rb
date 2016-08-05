# config valid only for current version of Capistrano
lock '3.5.0'

set :application, 'schooltv'
set :repo_url, 'git@git.interest-marketing.net:el-institute/schooltv.git'

# Default branch is :master
# ask :branch, `git rev-parse --abbrev-ref HEAD`.chomp

# Default deploy_to directory is /var/www/my_app_name
set :deploy_to, '/home/schooltv/application'

# Default value for :scm is :git
# set :scm, :git

# Default value for :format is :pretty
# set :format, :pretty

# Default value for :log_level is :debug
# set :log_level, :debug

# Default value for :pty is false
# set :pty, true

# Default value for :linked_files is []
# set :linked_files, fetch(:linked_files, []).push('config/database.yml', 'config/secrets.yml')

# Default value for linked_dirs is []
set :linked_dirs, fetch(:linked_dirs, []).push(
  'apps/web_external_api/application/logs',
  'apps/batch/application/logs',
  'apps/web_admin/application/logs',
  'apps/web_user/application/logs'
)

# Default value for temp_dirs is []
set :temp_dirs, fetch(:temp_dirs, []).push(
  'apps/web_external_api/application/tmp',
  'apps/batch/application/tmp',
  'apps/web_admin/application/tmp',
  'apps/web_user/application/tmp'
)

# Default value for default_env is {}
#set :default_env, { CODEIGNITER_ENV: "production" }
set :default_env, { CODEIGNITER_ENV: "#{fetch(:stage)}" }

# Default value for keep_releases is 5
set :keep_releases, 5

namespace :deploy do

  after :restart, :clear_cache do
    on roles(:web), in: :groups, limit: 3, wait: 10 do
      # Here we can do anything such as:
      # within release_path do
      #   execute :rake, 'cache:clear'
      # end
    end
  end

  after :updating, :create_temp_directory do
    on roles(:batch_user) do
      fetch(:temp_dirs, []).each do |path|
        execute "mkdir -p #{File.join(release_path, path, 'templates_c')}"
        execute "chmod -R 777 #{File.join(release_path, path)}"
      end
    end
  end

  after :updating, :create_schema_dump do
    on roles(:batch_user) do
      within release_path.join("tools") do
        execute "CODEIGNITER_ENV=#{fetch(:stage)} php #{release_path.join("tools", "ci.php")} schema_dump/execute all"
      end
    end
  end

  after :updating, :set_crontab do
#    crontab_file = release_path.join("shared", "config", "production", "crontab")
    crontab_file = release_path.join("shared", "config", "#{fetch(:stage)}", "crontab")
    on roles(:batch_user) do
      if test "[ -f '#{crontab_file}' ]"
        execute "crontab #{crontab_file}"
      end
    end
  end
end
