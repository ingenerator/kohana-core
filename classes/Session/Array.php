<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 */

class Session_Array extends \Session
{
    /**
     * @var string the serialised data
     */
    protected $data = NULL;

    /**
     * @var string the session ID
     */
    protected $id = NULL;

    /**
     * Loads the raw session data string and returns it.
     *
     * @param   string $id session id
     *
     * @return  string
     */
    protected function _read($id = NULL)
    {
        $this->id = $id;
        return $this->data;
    }

    /**
     * Generate a new session id and return it.
     *
     * @return  string
     */
    protected function _regenerate()
    {
        $this->id++;
        return $this->id;
    }

    /**
     * Writes the current session.
     *
     * @return  boolean
     */
    protected function _write()
    {
        $this->data = $this->__toString();
        return TRUE;
    }

    /**
     * Destroys the current session.
     *
     * @return  boolean
     */
    protected function _destroy()
    {
        $this->data = NULL;
        return TRUE;
    }

    /**
     * Restarts the current session.
     *
     * @return  boolean
     */
    protected function _restart()
    {
        return TRUE;
    }

    /**
     * Retrieves the current session ID
     *
     * @return string
     */
    public function id()
    {
        return $this->id;
    }

}
