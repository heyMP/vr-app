FROM node:8

# Sane defaults for setting up users
# https://github.com/nodejs/docker-node/blob/master/docs/BestPractices.md#global-npm-dependencies
# RUN npm install -g polymer-cli@next
# RUN npm install -g bower
# WORKDIR /home/node
WORKDIR /home/node/html
COPY package-lock.json package-lock.json
RUN npm install
EXPOSE 3000