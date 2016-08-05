
role :batch_user, %w{batch.schooltv.internal}

aws_elasticbeanstalk 'schooltv',
  type: :web_server,
  environments: %w{st-api-server3 st-admin-server2},
  bucket: 'elasticbeanstalk-ap-northeast-1-196240028071',
  extensions: {
    cloudwatch: {
      email: "alert+schooltv@interest-marketing.net",
      metrics: {
        # elb_healthy_host_count: 1,
        # elb_latency: 2,
        # elb_http_5xx: 1,
        # ec2_cpu_utilization: 60,
        # ec2_memory_utilization: 80,
        # ec2_disk_utilization: 80,
        ec2_status_check_failed: true,
      }
    },
    # mackerel: {
    #   apikey: "",
    #   plugins: [:apache, :linux]
    # },
    fluentd: {
      s3_bucket: "schooltv-log"
    },
     apache: {
       plugins: [:deflate]
#     },
#    zabbix_agent: {
#      server: '10.18.1.4',
#      api_username: 'api_schooltv',
#      api_password: 'szWv4GN1O9'
    }
  }

set :ssh_options, {
  user: 'schooltv',
  keys: %w(/home/deploy/.ssh/id_rsa),
  forward_agent: false,
  auth_methods: %w(publickey)
}

ask :branch, :master

after :deploy, "aws:elasticbeanstalk:deploy"

