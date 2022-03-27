FROM ubuntu:20.04
ARG DEBIAN_FRONTEND=noninteractive
RUN apt update -y && apt install -y php-cli unzip php php-curl curl && \
    curl -sS https://getcomposer.org/installer -o composer-setup.php && \
    HASH=`curl -sS https://composer.github.io/installer.sig` && \
    echo $HASH &&\
    php -r "if (hash_file('SHA384', 'composer-setup.php') === '$HASH') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" &&\
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
CMD ["/bin/bash"]