#!/usr/bin/env bash
set -euo pipefail

project_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
version="${1:-}"

if [[ -z "${version}" ]]; then
    echo "Usage: $0 VERSION" >&2
    exit 2
fi

build_dir="$(mktemp -d)"
trap 'rm -rf "${build_dir}"' EXIT

mkdir -p "${build_dir}/source" "${build_dir}/package/MauticPasskeyBundle" "${project_dir}/dist"
git -C "${project_dir}" archive HEAD | tar -x -C "${build_dir}/source"

composer install \
    --working-dir="${build_dir}/source/release-runtime" \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --classmap-authoritative

rm -rf "${build_dir}/source/release-runtime"

cp -a "${build_dir}/source/." "${build_dir}/package/MauticPasskeyBundle/"

tar -C "${build_dir}/package" -czf \
    "${project_dir}/dist/MauticPasskeyBundle-${version}.tar.gz" \
    MauticPasskeyBundle

(
    cd "${build_dir}/package"
    zip -q -r "${project_dir}/dist/MauticPasskeyBundle-${version}.zip" MauticPasskeyBundle
)

sha256sum \
    "${project_dir}/dist/MauticPasskeyBundle-${version}.tar.gz" \
    "${project_dir}/dist/MauticPasskeyBundle-${version}.zip"
