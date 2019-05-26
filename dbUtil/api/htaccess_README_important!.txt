.htaccess has been modified in this development environment to allow cross origin
since we are serving the .js and .html from our local node webpack server
but serving the db/php/mysql stuff (ie: api/. . .etc) from a locally installed UniServer
using a traditional WAMP style installation

this would (except for GET requests???) be a poor thing to allow in a production environment

therefore

remove

  Header set Access-Control-Allow-Origin "*"

from api/.htaccess
in a production build

