"""One heavy image/matcher job at a time, with a hard memory cap.

Every heavy intel script calls acquire() before loading images. It
  1. caps the process (and the ffmpeg/ImageMagick children it spawns) at
     FUJIBA_HEAVY_MEM_GB of address space (default 3) — a runaway job fails
     with MemoryError instead of pushing the kernel into a global OOM that
     can starve Redis/MySQL (2026-10-11 15:06 incident: a 2.4 GB matcher);
  2. takes an exclusive flock on /run/lock/fujiba-heavy.lock, waiting up to
     FUJIBA_HEAVY_WAIT seconds (default 3600), then exits 75 (EX_TEMPFAIL).

Nested guarded scripts (FUJIBA_HEAVY_HELD=1 inherited from a holder) skip the
lock so a holder can call another guarded script without deadlocking.
v1 live renderers (post_photo.py / post_reel.py) deliberately do NOT use this:
live posting must never wait behind an intel job.
"""
import fcntl
import json
import os
import resource
import sys
import time

LOCK = os.environ.get('FUJIBA_HEAVY_LOCK', '/run/lock/fujiba-heavy.lock')
_held = []   # keep the fd alive for the life of the process


def acquire(name, wait=None, mem_gb=None):
    mem_gb = float(mem_gb or os.environ.get('FUJIBA_HEAVY_MEM_GB', '3'))
    wait = int(wait if wait is not None else os.environ.get('FUJIBA_HEAVY_WAIT', '3600'))

    cap = int(mem_gb * 1024 ** 3)
    soft, hard = resource.getrlimit(resource.RLIMIT_AS)
    if hard == resource.RLIM_INFINITY or hard > cap:
        resource.setrlimit(resource.RLIMIT_AS, (cap, cap))
    elif soft != hard:
        resource.setrlimit(resource.RLIMIT_AS, (hard, hard))

    if os.environ.get('FUJIBA_HEAVY_HELD') == '1':
        return
    fd = os.open(LOCK, os.O_RDONLY | os.O_CREAT, 0o666)   # flock works on a read-only fd
    deadline = time.time() + wait
    while True:
        try:
            fcntl.flock(fd, fcntl.LOCK_EX | fcntl.LOCK_NB)
            break
        except BlockingIOError:
            if time.time() >= deadline:
                print(json.dumps({'error': 'heavy_lock_timeout', 'job': name, 'lock': LOCK}), flush=True)
                sys.exit(75)
            time.sleep(2)
    _held.append(fd)
    os.environ['FUJIBA_HEAVY_HELD'] = '1'
    try:
        with open(LOCK + '.holder', 'w') as f:
            f.write(f'{os.getpid()} {name} {time.strftime("%F %T")}\n')
    except OSError:
        pass
