<?php
namespace Nkey\GitProvider;

/**
 *
 * @author Maik
 */
class GitProviderException extends \Exception
{
    use Interpolator;

    /**
     * Creates a new GitProviderException
     *
     * @param string $message
     *            The message of the exception
     * @param array $context
     *            [Optional] Context parameters
     * @param number $code
     *            [Optional] Exception code
     * @param \Exception $previous
     *            [Optional] A previous exception to embed
     */
    public function __construct($message, $context = array(), $code = 0, $previous = null)
    {
        parent::__construct(self::interpolate($message, $context), $code, $previous);
    }
}
