<?php
namespace Nkey\GitProvider;

/**
 *
 * @author Maik Greubel <greubel@nkey.de>
 */
trait Interpolator
{

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param string $message
     *            The message containing placeholders
     * @param array $context
     *            The context array containing the replacers
     *
     * @return string The interpolated message
     */
    private static function interpolate($message, array $context = array())
    {
        $replace = array();

        if ($context !== null) {
            foreach ($context as $key => $val) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        return strtr($message, $replace);
    }
}
