# Layout [production]
* batch -> running batch on server
* web_admin/public_html -> admin.schooltv.jp
* web_external_api -> webapi.schooltv.jp
* web_user -> schooltv.jp

# Layout [local]
* batch -> running batch on server
* web_admin/public_html -> schooltv-admin.local
* web_external_api -> schooltv-webapi.local
* web_user -> schooltv.local

# Schema
Be careful this system using MySQL5.6 or later
If you are using MAMP PRO please refer
http://blog-en.mamp.info/

Must be sync with mwb to your environment
/schooltv/misc/schema/er.mwb

# Master data
Must be sync with these sql into your environment
/schooltv/misc/master/*.sql

Sample data of deck, question, video etc.. is in misc/sample
Please import
/schooltv/misc/sample/*.sql
to run drill and video pages.