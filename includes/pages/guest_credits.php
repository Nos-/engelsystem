<?php

/**
 * @return string
 */
function credits_title()
{
    return _('Credits');
}

/**
 * @return string
 */
function guest_credits()
{
    return view(__DIR__ . '/../../templates/pages/credits.html');
}
