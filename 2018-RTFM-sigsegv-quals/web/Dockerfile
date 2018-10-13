FROM debian:jessie-slim

RUN apt-get update -y && apt-get install -y nano build-essential python2.7 ipython python-pip

RUN useradd -m challenger

RUN apt-get install -y php5-cli

COPY ./docker-data-spoilers /challenge

WORKDIR /challenge

USER challenger

CMD php -S localhost:8080
