<?php
# This function reads your DATABASE_URL configuration automatically set by Heroku
# the return value is a string that will work with pg_connect
#function pg_connection_string() {
#  return "dbname=d1m0biglfdmgro host=ec2-107-21-96-109.compute-1.amazonaws.com port=5432 user=u36n2bvg7jfvsq password=p4fmjt7j28uv85f90m5iffcuk4 sslmode=require";
#}
 
# Establish db connection
#$db = pg_connect(pg_connection_string());
#if (!$db) {
#   echo "Database connection error."
#   exit;
#}
 
#$result = pg_query($db, "SELECT statement goes here");

#add in echos syntax error may be down to php version.
echo "Hello! Should be 5.4 now?";
phpinfo();

ngrender("grid.php", []);
?>