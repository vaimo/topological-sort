#!/usr/bin/env bash
: <<'COPYRIGHT'
 Copyright (c) Vaimo Group. All rights reserved.
 See LICENSE_VAIMO.txt for license details.
COPYRIGHT

vendor/bin/phpcbf -p src tests

exit 0
