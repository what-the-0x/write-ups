#!/bin/sh
docker build -t rtfm-quals-web .
docker run --rm -ti --net=host rtfm-quals-web
