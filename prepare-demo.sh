#!/bin/bash

# This script prepares the demo for deployment after using the app uploader in AppGini

# get script dir
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

# force move Northwind.axp outside app folder
mv "$DIR/app/orpm.axp" "$DIR/orpm.axp"