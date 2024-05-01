#!/bin/sh
aws ecr get-login-password --region us-east-1 | docker login --username AWS --password-stdin 755997884632.dkr.ecr.us-east-1.amazonaws.com
docker build -t cc-search -f Dockerfile.deploy --target release-stage .
docker tag cc-search:latest 755997884632.dkr.ecr.us-east-1.amazonaws.com/cc-search:latest
docker push 755997884632.dkr.ecr.us-east-1.amazonaws.com/cc-search:latest