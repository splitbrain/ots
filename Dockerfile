FROM debian AS build
RUN apt-get update && apt-get install -y build-essential git libglib2.0-dev libxml2-dev libpopt-dev
RUN git clone https://github.com/neopunisher/Open-Text-Summarizer.git ots
WORKDIR /ots
RUN ./configure --prefix=/opt
RUN touch gtk-doc.make # workaround for missing gtk-doc.make
RUN make
RUN make install
RUN ls -lR /opt


FROM php:8.2-apache
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN apt-get update && apt-get install -y libglib2.0-0 libxml2 libpopt0 && apt-get clean

COPY --from=build /opt/ /opt/
COPY index.php /var/www/html/index.php
