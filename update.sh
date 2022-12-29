#!/bin/bash

#php easyswoole start d produce

git pull

rm -f ./Log/* ./Static/Log/*

php easyswoole restart produce
