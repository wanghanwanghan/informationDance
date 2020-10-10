#!/bin/bash

git pull

composer install

php easyswoole restart
