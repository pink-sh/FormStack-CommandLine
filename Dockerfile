FROM php:5.6-apache
COPY . /usr/src/formstack
WORKDIR /usr/src/formstack
CMD [ "php", "./run.php" ]
