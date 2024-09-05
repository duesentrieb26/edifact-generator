#!/bin/bash

# Step 1: Read the current version from composer.json
current_version=$(grep -oP '(?<="version": ")[^"]*' composer.json)

# Step 2: Increment the version number
IFS='.' read -r -a version_parts <<< "$current_version"
((version_parts[2]++))
new_version="${version_parts[0]}.${version_parts[1]}.${version_parts[2]}"

# Step 3: Update the composer.json file with the new version
sed -i "s/\"version\": \"$current_version\"/\"version\": \"$new_version\"/" composer.json

# Step 4: Commit the changes to Git
git add composer.json
git commit -m "Bump version to $new_version"

# Step 5: Create a new Git tag with the new version
git tag -a "$new_version" -m "Version $new_version"
git push origin --tags