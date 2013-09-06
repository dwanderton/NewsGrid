<?php

    /***********************************************************************
     * functions.php
     *
     * credit to cs50
     * Helper functions.
     **********************************************************************/

    require_once("constants.php");

    /**
     * Apologizes to user with message.
     */
    function apologize($message)
    {
        render("apology.php", ["message" => $message]);
        exit;
    }

    /**
     * Facilitates debugging by dumping contents of variable
     * to browser.
     */
    function dump($variable)
    {
        require("../templates/dump.php");
        exit;
    }

    /**
     * Logs out current user, if any.  Based on Example #1 at
     * http://us.php.net/manual/en/function.session-destroy.php.
     */
    function logout()
    {
        // unset any session variables
        $_SESSION = array();

        // expire cookie
        if (!empty($_COOKIE[session_name()]))
        {
            setcookie(session_name(), "", time() - 42000);
        }

        // destroy session
        session_destroy();
    }

    /**
     * Returns a stock by symbol (case-insensitively) else false if not found.
     */
    function lookup($symbol)
    {
        // reject symbols that start with ^
        if (preg_match("/^\^/", $symbol))
        {
            return false;
        }

        // reject symbols that contain commas
        if (preg_match("/,/", $symbol))
        {
            return false;
        }

        // open connection to Yahoo
        $handle = @fopen("http://download.finance.yahoo.com/d/quotes.csv?f=snl1&s=$symbol", "r");
        if ($handle === false)
        {
            // trigger (big, orange) error
            trigger_error("Could not connect to Yahoo!", E_USER_ERROR);
            exit;
        }

        // download first line of CSV file
        $data = fgetcsv($handle);
        if ($data === false || count($data) == 1)
        {
            return false;
        }

        // close connection to Yahoo
        fclose($handle);

        // ensure symbol was found
        if ($data[2] === "0.00")
        {
            return false;
        }

        // return stock as an associative array
        return [
            "symbol" => $data[0],
            "name" => $data[1],
            "price" => $data[2],
        ];
    }

    /**
     * Executes SQL statement, possibly with parameters, returning
     * an array of all rows in result set or false on (non-fatal) error.
     */
    function query(/* $sql [, ... ] */)
    {
        // SQL statement
        $sql = func_get_arg(0);

        // parameters, if any
        $parameters = array_slice(func_get_args(), 1);

        // try to connect to database
        static $handle;
        if (!isset($handle))
        {
            try
            {
                // connect to database
                $handle = new PDO("mysql:dbname=" . DATABASE . ";host=" . SERVER, USERNAME, PASSWORD);

                // ensure that PDO::prepare returns false when passed invalid SQL
                $handle->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); 
            }
            catch (Exception $e)
            {
                // trigger (big, orange) error
                trigger_error($e->getMessage(), E_USER_ERROR);
                exit;
            }
        }

        // prepare SQL statement
        $statement = $handle->prepare($sql);
        if ($statement === false)
        {
            // trigger (big, orange) error
            trigger_error($handle->errorInfo()[2], E_USER_ERROR);
            exit;
        }

        // execute SQL statement
        $results = $statement->execute($parameters);

        // return result set's rows, if any
        if ($results !== false)
        {
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        else
        {
            return false;
        }
    }

    function ngquery(/* $sql [, ... ] */)
    {
        // SQL statement
        $sql = func_get_arg(0);

        // parameters, if any
        $parameters = array_slice(func_get_args(), 1);

        // try to connect to database
        static $handle;
        if (!isset($handle))
        {
            try
            {
                // connect to database
                $handle = new PDO("mysql:dbname=" . NGDATABASE . ";host=" . SERVER, USERNAME, PASSWORD);

                // ensure that PDO::prepare returns false when passed invalid SQL
                $handle->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); 
            }
            catch (Exception $e)
            {
                // trigger (big, orange) error
                trigger_error($e->getMessage(), E_USER_ERROR);
                exit;
            }
        }

        // prepare SQL statement
        $statement = $handle->prepare($sql);
        if ($statement === false)
        {
            // trigger (big, orange) error
            trigger_error($handle->errorInfo()[2], E_USER_ERROR);
            exit;
        }

        // execute SQL statement
        $results = $statement->execute($parameters);

        // return result set's rows, if any
        if ($results !== false)
        {
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        else
        {
            return false;
        }
    }


    function updatebbcnewsdb()
    /** download the latest batch of bbc stories and post in an array 
    * if there is a duplicate thumbnail the script should update the current story on the grid. However this is not yet fully tested.
    * timstamp functions http://forum.openoffice.org/en/forum/viewtopic.php?f=13&t=49795
    * this function replaced the defunct latestbbc()
    **/
    {
        $databbc = @file_get_contents("http://api.bbcnews.appengine.co.uk/stories/world");
        $bbcnewsjson = json_decode($databbc);
        $bbcnewsstories = $bbcnewsjson->{'stories'};
        $bbcreturn = array();
        $i=0;
        foreach ($bbcnewsstories as $story => $details) {
            $bbcreturn[$i][0] = $details->{'title'};

            /** test initially if the thumbnail returns a 404 **/
            $thumbhead = print_r(get_headers($details->{'thumbnail'}, 1));            
            if ($thumbhead[0] != 'HTTP/1.1 404 Not Found' || $details->{'thumbnail'} != "http://news.bbcimg.co.uk/media/images/68661000/jpg/_68661706_bn-144x81.jpg" || $details->{'thumbnail'} != "http://news.bbcimg.co.uk/media/images/68661000/jpg/_68661377_bn-448x252.jpg" || $details->{'thumbnail'} != "http://news.bbcimg.co.uk/media/images/68662000/jpg/_68662686_bn-144x81.jpg") {
            $bbcreturn[$i][1] = $details->{'thumbnail'};
            }
            else{
            $bbcreturn[$i][1] = "img/404thumb.png";
            }
            $bbcreturn[$i][2] = $details->{'description'};
            $bbcreturn[$i][3] = $details->{'link'};
            $bbcreturn[$i][4] = $details->{'published'};
            $i++;
        }


        foreach($bbcreturn as $story){
            $attempttoinsert = ngquery("INSERT INTO bbcstory (title, thumbnail, description, link, published) VALUES (?,?,?,?,?);", $story[0], $story[1], $story[2], $story[3], $story[4]);
            if ($attempttoinsert === false){
                $attempttoupdate = ngquery("UPDATE bbcstory SET title = ?, description = ?, link =?, published = ? WHERE thumbnail = ?", $story[0], $story[2], $story[3], $story[4], $story[1]);    
            }
        }
        return;
    }


    /**
     * Redirects user to destination, which can be
     * a URL or a relative path on the local host.
     *
     * Because this function outputs an HTTP header, it
     * must be called before caller outputs any HTML.
     */
    function redirect($destination)
    {
        // handle URL
        if (preg_match("/^https?:\/\//", $destination))
        {
            header("Location: " . $destination);
        }

        // handle absolute path
        else if (preg_match("/^\//", $destination))
        {
            $protocol = (isset($_SERVER["HTTPS"])) ? "https" : "http";
            $host = $_SERVER["HTTP_HOST"];
            header("Location: $protocol://$host$destination");
        }

        // handle relative path
        else
        {
            // adapted from http://www.php.net/header
            $protocol = (isset($_SERVER["HTTPS"])) ? "https" : "http";
            $host = $_SERVER["HTTP_HOST"];
            $path = rtrim(dirname($_SERVER["PHP_SELF"]), "/\\");
            header("Location: $protocol://$host$path/$destination");
        }

        // exit immediately since we're redirecting anyway
        exit;
    }

    /**
     * Renders template, passing in values.
     */
    function render($template, $values = [])
    {
        // if template exists, render it
        if (file_exists("../templates/$template"))
        {
            // extract variables into local scope
            extract($values);

            // render header
            require("../templates/header.php");

            // render template
            require("../templates/$template");

            // render footer
            require("../templates/footer.php");
        }

        // else err
        else
        {
            trigger_error("Invalid template: $template", E_USER_ERROR);
        }
    }
    /**
     * Renders template, passing in values.
     */
    function ngrender($template, $values = [])
    {
        // if template exists, render it
        if (file_exists("/templates/$template"))
        {
            // extract variables into local scope
            extract($values);

            // render header
            require("./templates/ngheader.php");

            // render template
            require("./templates/$template");

        }

        // else err
        else
        {
            trigger_error("Invalid template: $template", E_USER_ERROR);
        }
    }

?>
