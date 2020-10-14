#!/bin/bash

git pull

composer install

rm -f ./Log/* ./Static/Log/*

php easyswoole restart
