#!/bin/bash

git pull

rm -f ./Log/* ./Static/Log/*

php easyswoole restart produce
