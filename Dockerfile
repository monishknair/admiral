FROM nginx

RUN apt-get update \
&& apt-get install -y php5-common php5-fpm git php5-mysql supervisor \
&& git clone --recursive git://github.com/TestArmada/admiral.git /app

RUN cp /app/config/sample-nginx.conf /etc/nginx/conf.d/default.conf

RUN sed -i s/www-data/nginx/ /etc/php5/fpm/pool.d/www.conf
RUN sed -i "s/127.0.0.1:9000/unix:\/var\/run\/php5-fpm.sock/" /etc/nginx/conf.d/default.conf

RUN cd /app;/usr/bin/php external/ua-parser/php/uaparser-cli.php -g

# Setup Admiral config files
RUN cp /app/config/sample-localSettings.php /app/config/localSettings.php

ARG MYSQL_HOST
ARG MYSQL_DB
ARG MYSQL_USER
ARG MYSQL_PASS

# Setup localSettings.json with DB credentials and root context
RUN chmod 777 /app/cache
RUN cp /app/config/sample-localSettings.json /app/config/localSettings.json
RUN sed -i s/'"database": "testswarm"'/"\"database\": \"$MYSQL_DB\""/ /app/config/localSettings.json
RUN sed -i s/'"host": "localhost"'/"\"host\": \"$MYSQL_HOST\""/ /app/config/localSettings.json
RUN sed -i s/'"username": "root"'/"\"username\": \"$MYSQL_USER\""/ /app/config/localSettings.json
RUN sed -i s/'"password": "root"'/"\"password\": \"$MYSQL_PASS\""/ /app/config/localSettings.json
RUN sed -i s/'"contextpath": "testswarm"'/"\"contextpath\": \"\/\""/ /app/config/localSettings.json
RUN sed -i s/'"title": "TestSwarm"'/"\"title\": \"Admiral\""/ /app/config/defaultSettings.json
RUN sed -i "s/var\/www\/testswarm/app/" /etc/nginx/conf.d/default.conf
RUN sed -i "s/var\/www\/swarm/app/" /etc/nginx/conf.d/default.conf

RUN php /app/scripts/install.php --quiet

EXPOSE 80

CMD  /etc/init.d/php5-fpm start && /etc/init.d/nginx start && exec supervisord -n
