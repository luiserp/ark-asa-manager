FROM steamcmd/steamcmd:ubuntu-22

LABEL maintainer="ark-asa-server"

ARG PGID=1000 \
    PUID=1000 \
    PROTON_GE_VAR=wine-lutris \
    PROTON_GE_VER=GE-Proton8-21 \
    PROTON_GE_ARCH=x86_64

ENV TZ=America/New_York \
    SERVER_DIR="/ark-server/server" \
    BACKUPS_DIR="/ark-server/backups" \
    LOGS_DIR="/ark-server/logs"

# ── System packages ──────────────────────────────────────────────────
RUN set -x && \
    apt-get update && \
    apt-get install --no-install-recommends -y \
        wget \
        xz-utils \
        xvfb \
        cron \
        tzdata \
        zip \
        unzip \
        python3 \
        openssh-server \
        ca-certificates && \
    rm -rf /var/lib/apt/lists/*

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# ── Wine / Proton GE (required to run the Windows ARK server binary) ─
WORKDIR /opt/glorious_eggroll
RUN PROTON_GE_FILE="$PROTON_GE_VAR-$PROTON_GE_VER-$PROTON_GE_ARCH" && \
    wget -q "https://github.com/GloriousEggroll/wine-ge-custom/releases/download/$PROTON_GE_VER/$PROTON_GE_FILE.tar.xz" && \
    tar -xvf "$PROTON_GE_FILE.tar.xz" && \
    mkdir -p ~/.proton && \
    mv "lutris-$PROTON_GE_VER-$PROTON_GE_ARCH" ./proton && \
    rm -r "$PROTON_GE_FILE.tar.xz"

# ── XAudio2 DLL (runtime dependency for ARK server binary) ───────────
RUN wget "https://www.nuget.org/api/v2/package/Microsoft.XAudio2.Redist/1.2.11" -O xaudio2.nupkg && \
    unzip xaudio2.nupkg -d xaudio2_extract && \
    mkdir -p $SERVER_DIR/ShooterGame/Binaries/Win64/ && \
    cp xaudio2_extract/build/native/release/bin/x64/xaudio2_9redist.dll $SERVER_DIR/ShooterGame/Binaries/Win64/xaudio2_9.dll && \
    chmod 775 $SERVER_DIR/ShooterGame/Binaries/Win64/xaudio2_9.dll && \
    rm -rf xaudio2.nupkg xaudio2_extract

# ── User setup ────────────────────────────────────────────────────────
RUN groupadd -g "$PGID" -o ark-sa && \
    useradd -l -g "$PGID" -u "$PUID" -o -m -s /bin/bash ark-sa && \
    usermod -a -G crontab ark-sa && \
    passwd -d ark-sa

RUN mkdir -p /ark-server/server /ark-server/logs /ark-server/backups && \
    chown -R ark-sa:ark-sa /ark-server

# ── SSH setup ─────────────────────────────────────────────────────────
RUN mkdir -p /run/sshd && \
    mkdir -p /home/ark-sa/.ssh && \
    chmod 700 /home/ark-sa/.ssh && \
    chown ark-sa:ark-sa /home/ark-sa/.ssh

COPY docker/ark-server/sshd_config /etc/ssh/sshd_config
COPY docker/ark-server/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# ── Volumes & ports ──────────────────────────────────────────────────
VOLUME ["/ark-server"]

WORKDIR ${SERVER_DIR}

EXPOSE 22/tcp
EXPOSE 7777/udp
EXPOSE 7778/udp
EXPOSE 27015/udp
EXPOSE 27020/tcp

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/sbin/sshd", "-D"]
