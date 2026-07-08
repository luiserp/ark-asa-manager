#!/usr/bin/env bash

set -e

# ── Ensure /ark-server directories exist and are owned by ark-sa ────
mkdir -p /ark-server/server /ark-server/logs /ark-server/backups
chown ark-sa:ark-sa /ark-server /ark-server/server /ark-server/logs /ark-server/backups

# ── SSH host keys ─────────────────────────────────────────────────────
if [ ! -f /etc/ssh/ssh_host_rsa_key ]; then
    ssh-keygen -t rsa -f /etc/ssh/ssh_host_rsa_key -N ''
    ssh-keygen -t ecdsa -f /etc/ssh/ssh_host_ecdsa_key -N ''
    ssh-keygen -t ed25519 -f /etc/ssh/ssh_host_ed25519_key -N ''
fi

# ── Authorize the public key from the shared volume ───────────────────
if [ -f /shared-ssh/id_ed25519.pub ]; then
    tr -d '\r' < /shared-ssh/id_ed25519.pub > /home/ark-sa/.ssh/authorized_keys
    chmod 600 /home/ark-sa/.ssh/authorized_keys
    chown ark-sa:ark-sa /home/ark-sa/.ssh/authorized_keys
    echo "SSH public key authorized for ark-sa user."
else
    echo "WARNING: No public key found at /shared-ssh/id_ed25519.pub — SSH login will not work."
fi

exec "$@"
