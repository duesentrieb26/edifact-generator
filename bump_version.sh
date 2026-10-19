#!/bin/bash
# Stop at the first failing step, so a failed bump never creates or pushes a tag
set -euo pipefail

# Step 1: Read the current version from composer.json
current_version=$(sed -n 's/.*"version": "\([^"]*\)".*/\1/p' composer.json)
if [ -z "$current_version" ]; then
  echo "No version found in composer.json" >&2
  exit 1
fi

# Step 2: Increment the patch number
IFS='.' read -r -a version_parts <<< "$current_version"
version_parts[2]=$((version_parts[2] + 1))
new_version="${version_parts[0]}.${version_parts[1]}.${version_parts[2]}"

# Step 3: Update composer.json (-i.bak works with GNU and BSD/macOS sed)
sed -i.bak "s/\"version\": \"$current_version\"/\"version\": \"$new_version\"/" composer.json
rm composer.json.bak

# Step 4: Commit the changes to Git
git add composer.json
git commit -m "Bump version to $new_version"

# Step 5: Create the tag and push only this tag
git tag -a "$new_version" -m "Version $new_version"
git push origin "$new_version"
