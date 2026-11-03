#!/usr/bin/env bash
set -euo pipefail

base_url="${1:-http://127.0.0.1:8000}"

check_status() {
    local expected="$1" path="$2" actual

    actual="$(curl \
        --silent \
        --output /dev/null \
        --write-out '%{http_code}' \
        "${base_url}${path}")"

    if [[ "$actual" != "$expected" ]]; then
        printf 'FAIL %s: expected HTTP %s, got %s\n' \
            "$path" "$expected" "$actual" >&2
        exit 1
    fi

    printf 'PASS %-22s HTTP %s\n' "$path" "$actual"
}

check_status 200 /api/v2/users
check_status 200 /api/v2/users/1
check_status 404 /api/v2/users/999
check_status 400 /api/v2/users/abc

curl --fail --silent --show-error \
    "${base_url}/api/v2/users" | php -r '
    $response = json_decode(
        stream_get_contents(STDIN),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (($response["meta"]["api_version"] ?? null) !== "v2" ||
        ($response["meta"]["count"] ?? null) !== 2 ||
        ($response["data"][0]["id"] ?? null) !== 1) {
        fwrite(STDERR, "FAIL: unexpected users JSON\n");
        exit(1);
    }

    echo "PASS JSON response shape\n";
'
