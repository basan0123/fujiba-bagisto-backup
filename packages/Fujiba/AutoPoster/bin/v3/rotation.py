"""Pure family rotation: next enabled family after `previous`, never repeating it."""


def next_family(order, enabled, previous=None):
    """order: family ids in rotation order; enabled: set of ids that can render.
    Returns the first enabled id after `previous` (wrapping) that differs from it,
    or None if nothing else is enabled."""
    if not order:
        return None
    start = order.index(previous) + 1 if previous in order else 0
    for i in range(len(order)):
        fam = order[(start + i) % len(order)]
        if fam in enabled and fam != previous:
            return fam
    return None
