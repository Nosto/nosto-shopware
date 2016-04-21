<?php
/**
 * Copyright (c) 2016, Nosto Solutions Ltd
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its contributors
 * may be used to endorse or promote products derived from this software without
 * specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2016 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

/**
 * Class representation of the original phpseclib implementation with a few modifications:
 *
 * - additional error checking
 * - openssl_random_pseudo_bytes() output is required to be cryptographically strong
 * - mcrypt_create_iv() is preferred method on Linux
 * - Only AES cipher is available when generating pure-PHP CSPRNG
 */
class NostoCryptRandom
{
    /**
     * Returns a cryptographically string random string.
     *
     * @param int $length the length of the string to generate.
     * @return string the generated random string.
     */
    public static function getRandomString($length)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            /*
             * Prior to PHP 5.3 this would call rand() on win, hence the function_exists('class_alias') call.
             * Function class_alias was introduced in PHP 5.3.
             */
            if (function_exists('mcrypt_create_iv') && function_exists('class_alias')) {
                $rnd = mcrypt_create_iv($length);
                if ($rnd !== false) {
                    return $rnd;
                }
            }
            /*
             * Function openssl_random_pseudo_bytes was introduced in PHP 5.3.0 but prior to PHP 5.3.4 there was a
             * "possible blocking behavior". As of 5.3.4 openssl_random_pseudo_bytes and mcrypt_create_iv do the exact
             * same thing on Windows. ie. they both call php_win32_get_random_bytes().
             *
             * @link http://php.net/ChangeLog-5.php#5.3.4
             * @link https://github.com/php/php-src/blob/7014a0eb6d1611151a286c0ff4f2238f92c120d6/ext/openssl/openssl.c#L5008
             * @link https://github.com/php/php-src/blob/7014a0eb6d1611151a286c0ff4f2238f92c120d6/ext/mcrypt/mcrypt.c#L1392
             * @link https://github.com/php/php-src/blob/7014a0eb6d1611151a286c0ff4f2238f92c120d6/win32/winutil.c#L80
             */
            if (function_exists('openssl_random_pseudo_bytes') && version_compare(PHP_VERSION, '5.3.4', '>=')) {
                $strong = null;
                $rnd = openssl_random_pseudo_bytes($length, $strong);
                if ($strong) {
                    return $rnd;
                }
            }
        } else {
            if (function_exists('mcrypt_create_iv')) {
                $rnd = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
                if ($rnd !== false) {
                    return $rnd;
                }
            }
            if (function_exists('openssl_random_pseudo_bytes')) {
                $strong = null;
                $rnd = openssl_random_pseudo_bytes($length, $strong);
                if ($strong) {
                    return $rnd;
                }
            }
            if (file_exists('/dev/urandom') && is_readable('/dev/urandom')) {
                if (($fp = @fopen('/dev/urandom', 'rb')) !== false) {
                    if (function_exists('stream_set_read_buffer')) {
                        stream_set_read_buffer($fp, 0);
                    }
                    $rnd = fread($fp, $length);
                    fclose($fp);
                    if ($rnd !== false) {
                        return $rnd;
                    }
                }
            }
        }
        /*
         * At this point we have no choice but to use a pure-PHP CSPRNG.
         *
         * Cascade entropy across multiple PHP instances by fixing the session and collecting all  environmental
         * variables, including the previous session data and the current session data.
         *
         * Function mt_rand seeds itself by looking at the PID and the time, both of which are (relatively) easy to
         * guess. linux uses mouse clicks, keyboard timings, etc, as entropy sources, but PHP isn't low level enough to
         * be able to use those as sources and on a web server there's not likely going to be a ton of keyboard or mouse
         * action. Web servers do have one thing that we can use, however, a ton of people visiting the website.
         * Obviously you don't want to base your seeding solely on parameters a potential attacker sends but (1) not
         * everything in $_SERVER is controlled by the user and (2) this isn't just looking at the data sent by the
         * current user - it's based on the data sent by all users. One user requests the page and a hash of their info
         * is saved. Another user visits the page and the serialization of their data is utilized along with the server
         * environment stuff and a hash of the previous http request data (which itself utilizes a hash of the session
         * data before that). Certainly an attacker should be assumed to have full control over his own http requests.
         * He, however, is not going to have control over all users http requests.
         */

        // Save old session data.
        $old_session_id = session_id();
        $old_use_cookies = ini_get('session.use_cookies');
        $old_session_cache_limiter = session_cache_limiter();
        $_OLD_SESSION = isset($_SESSION) ? $_SESSION : false;
        if ($old_session_id != '') {
            session_write_close();
        }

        session_id(1);
        ini_set('session.use_cookies', 0);
        session_cache_limiter('');
        session_start();

        $v = $seed = $_SESSION['seed'] = pack(
            'H*',
            sha1(
                serialize($_SERVER).
                serialize($_COOKIE).
                serialize($GLOBALS).
                serialize($_SESSION).
                serialize($_OLD_SESSION)
            )
        );
        if (!isset($_SESSION['count'])) {
            $_SESSION['count'] = 0;
        }
        $_SESSION['count']++;

        session_write_close();

        // Restore old session data.
        if ($old_session_id != '') {
            session_id($old_session_id);
            session_start();
            ini_set('session.use_cookies', $old_use_cookies);
            session_cache_limiter($old_session_cache_limiter);
        } else {
            if ($_OLD_SESSION !== false) {
                $_SESSION = $_OLD_SESSION;
                unset($_OLD_SESSION);
            } else {
                unset($_SESSION);
            }
        }

        /*
         * In SSH2 a shared secret and an exchange hash are generated through the key exchange process.
         * The IV client to server is the hash of that "nonce" with the letter A and for the encryption key it's the
         * letter C. If the hash doesn't produce enough a key or an IV that's long enough concatenate successive hashes
         * of the original hash and the current hash.
         *
         * @link http://tools.ietf.org/html/rfc4253#section-7.2
         */

        $key = pack('H*', sha1($seed.'A'));
        $iv = pack('H*', sha1($seed.'C'));

        /*
         * Ciphers are used as per the nist.gov.
         *
         * @link http://en.wikipedia.org/wiki/Cryptographically_secure_pseudorandom_number_generator#Designs_based_on_cryptographic_primitives
         */
        $crypt = new NostoCryptAES(CRYPT_AES_MODE_CTR);
        $crypt->setKey($key);
        $crypt->setIV($iv);
        $crypt->enableContinuousBuffer();

        /*
         * The following is based off of ANSI X9.31.
         * OpenSSL uses that same standard for it's random numbers.
         *
         * @link http://csrc.nist.gov/groups/STM/cavp/documents/rng/931rngext.pdf
         * @link http://www.opensource.apple.com/source/OpenSSL/OpenSSL-38/openssl/fips-1.0/rand/fips_rand.c
         */
        $rnd = '';
        while (strlen($rnd) < $length) {
            $i = $crypt->encrypt(microtime()); // strlen(microtime()) == 21
            $r = $crypt->encrypt($i ^ $v); // strlen($v) == 20
            $v = $crypt->encrypt($r ^ $i); // strlen($r) == 20
            $rnd .= $r;
        }
        return substr($rnd, 0, $length);
    }
}
