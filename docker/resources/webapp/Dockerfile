FROM ubuntu

RUN apt-get -y update && echo '2022-04-14'
RUN apt -y install software-properties-common && add-apt-repository ppa:ondrej/php && apt-get update
RUN DEBIAN_FRONTEND=noninteractive apt -y install php8.0 
RUN apt -y install php8.0-mysql
RUN apt-get -y install php8.0-xml
RUN apt-get -y install php8.0-mbstring
RUN apt-get -y install php8.0-mongodb
RUN apt-get update -y && apt-get install -y php8.0-curl
RUN apt install -y php8.0-sqlite3
RUN apt-get update && apt-get -y install curl


COPY files/main-site.conf /etc/apache2/sites-available/000-default.conf
RUN chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite

#
# Install composer
#
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
php composer-setup.php --install-dir=/usr/local/bin  --filename=composer && \
php -r "unlink('composer-setup.php');"

#
# upgrade nodejs to 14v
#
RUN curl -sL https://deb.nodesource.com/setup_14.x | bash -
RUN apt-get install -y nodejs
RUN apt-get -y install zip unzip vim
#
#install yarn
#
RUN curl -sL https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
RUN apt update && apt -y install yarn
#
# Code Coverage
#
RUN apt-get -y install php8.0-xdebug
RUN echo xdebug.mode=coverage >> /etc/php/8.0/cli/conf.d/20-xdebug.ini
CMD /usr/sbin/apache2ctl -D FOREGROUND

# Set default work directory
WORKDIR /var/www/html
