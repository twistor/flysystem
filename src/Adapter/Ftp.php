<?php

declare(strict_types=1);

namespace League\Flysystem\Adapter;

use ErrorException;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Adapter\DirectoryMetadata;
use League\Flysystem\Adapter\MetadataInterface;
use League\Flysystem\Adapter\Polyfill\StreamedCopyTrait;
use League\Flysystem\Config;
use League\Flysystem\Util;
use RuntimeException;

class Ftp extends AbstractFtpAdapter
{
    use StreamedCopyTrait;

    /**
     * @var int
     */
    protected $transferMode = FTP_BINARY;

    /**
     * @var null|bool
     */
    protected $ignorePassiveAddress = null;

    /**
     * @var bool
     */
    protected $recurseManually = false;

    /**
     * @var bool
     */
    protected $utf8 = false;

    /**
     * @var array
     */
    protected $configurable = [
        'host',
        'port',
        'username',
        'password',
        'ssl',
        'timeout',
        'root',
        'permPrivate',
        'permPublic',
        'passive',
        'transferMode',
        'systemType',
        'ignorePassiveAddress',
        'recurseManually',
        'utf8',
    ];

    /**
     * @var bool
     */
    protected $isPureFtpd;

    /**
     * Set the transfer mode.
     *
     * @param int $mode
     *
     * @return $this
     */
    public function setTransferMode(int $mode)
    {
        $this->transferMode = $mode;

        return $this;
    }

    /**
     * Set if Ssl is enabled.
     *
     * @param bool $ssl
     *
     * @return $this
     */
    public function setSsl(bool $ssl)
    {
        $this->ssl = $ssl;

        return $this;
    }

    /**
     * Set if passive mode should be used.
     *
     * @param bool $passive
     */
    public function setPassive(bool $passive = true)
    {
        $this->passive = $passive;
    }

    /**
     * @param bool $ignorePassiveAddress
     */
    public function setIgnorePassiveAddress(bool $ignorePassiveAddress)
    {
        $this->ignorePassiveAddress = $ignorePassiveAddress;
    }

    /**
     * @param bool $recurseManually
     */
    public function setRecurseManually(bool $recurseManually)
    {
        $this->recurseManually = $recurseManually;
    }

    /**
     * @param bool $utf8
     */
    public function setUtf8(bool $utf8)
    {
        $this->utf8 = $utf8;
    }

    /**
     * Connect to the FTP server.
     */
    public function connect()
    {
        if ($this->ssl) {
            $this->connection = ftp_ssl_connect($this->getHost(), $this->getPort(), $this->getTimeout());
        } else {
            $this->connection = ftp_connect($this->getHost(), $this->getPort(), $this->getTimeout());
        }

        if ( ! $this->connection) {
            throw new RuntimeException('Could not connect to host: ' . $this->getHost() . ', port:' . $this->getPort());
        }

        $this->login();
        $this->setUtf8Mode();
        $this->setConnectionPassiveMode();
        $this->setConnectionRoot();
        $this->isPureFtpd = $this->isPureFtpdServer();
    }

    /**
     * Set the connection to UTF-8 mode.
     */
    protected function setUtf8Mode()
    {
        if ($this->utf8) {
            $response = ftp_raw($this->connection, "OPTS UTF8 ON");
            if (substr($response[0], 0, 3) !== '200') {
                throw new RuntimeException(
                    'Could not set UTF-8 mode for connection: ' . $this->getHost() . '::' . $this->getPort()
                );
            }
        }
    }

    /**
     * Set the connections to passive mode.
     *
     * @throws RuntimeException
     */
    protected function setConnectionPassiveMode()
    {
        if (is_bool($this->ignorePassiveAddress) && defined('FTP_USEPASVADDRESS')) {
            ftp_set_option($this->connection, FTP_USEPASVADDRESS, ! $this->ignorePassiveAddress);
        }

        if ( ! ftp_pasv($this->connection, $this->passive)) {
            throw new RuntimeException(
                'Could not set passive mode for connection: ' . $this->getHost() . '::' . $this->getPort()
            );
        }
    }

    /**
     * Set the connection root.
     */
    protected function setConnectionRoot()
    {
        $root = $this->getRoot();
        $connection = $this->connection;

        if (empty($root) === false && ! ftp_chdir($connection, $root)) {
            throw new RuntimeException('Root is invalid or does not exist: ' . $this->getRoot());
        }

        // Store absolute path for further reference.
        // This is needed when creating directories and
        // initial root was a relative path, else the root
        // would be relative to the chdir'd path.
        $this->root = ftp_pwd($connection);
    }

    /**
     * Login.
     *
     * @throws RuntimeException
     */
    protected function login()
    {
        set_error_handler(function () {});
        $isLoggedIn = ftp_login(
            $this->connection,
            $this->getUsername(),
            $this->getPassword()
        );
        restore_error_handler();

        if ( ! $isLoggedIn) {
            $this->disconnect();
            throw new RuntimeException(
                'Could not login with connection: ' . $this->getHost() . '::' . $this->getPort(
                ) . ', username: ' . $this->getUsername()
            );
        }
    }

    /**
     * Disconnect from the FTP server.
     */
    public function disconnect()
    {
        if (is_resource($this->connection)) {
            ftp_close($this->connection);
        }

        $this->connection = null;
    }

    /**
     * @inheritdoc
     */
    public function write(string $path, string $contents, Config $config): ?array
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $contents);
        rewind($stream);
        $result = $this->writeStream($path, $stream, $config);
        fclose($stream);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function writeStream(string $path, $resource, Config $config): ?array
    {
        $this->ensureDirectory(Util::dirname($path));

        if ( ! ftp_fput($this->getConnection(), $path, $resource, $this->transferMode)) {
            return;
        }

        if ($visibility = $config->get('visibility')) {
            $this->setVisibility($path, $visibility);
        }

        return ['visibility' => $visibility];
    }

    /**
     * @inheritdoc
     */
    public function update(string $path, string $contents, Config $config): ?array
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * @inheritdoc
     */
    public function updateStream(string $path, $resource, Config $config): ?array
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * @inheritdoc
     */
    public function rename(string $path, string $newpath, Config $config): bool
    {
        return ftp_rename($this->getConnection(), $path, $newpath);
    }

    /**
     * @inheritdoc
     */
    public function deleteFile(string $path, Config $config): bool
    {
        return ftp_delete($this->getConnection(), $path);
    }

    /**
     * @inheritdoc
     */
    public function deleteDir(string $path, Config $config): bool
    {
        $connection = $this->getConnection();
        $contents = array_reverse($this->listDirectoryContents($path));

        foreach ($contents as $key => $object) {
            if ($object['type'] !== 'file') {
                continue;
            }

            unset($contents[$key]);

            if ( ! ftp_delete($connection, $object['path'])) {
                return false;
            }
        }

        foreach ($contents as $object) {
            if ( ! ftp_rmdir($connection, $object['path'])) {
                return false;
            }
        }

        return ftp_rmdir($connection, $path);
    }

    /**
     * @inheritdoc
     */
    public function createDir(string $path, Config $config): bool
    {
        $connection = $this->getConnection();
        $directories = explode('/', $path);

        foreach ($directories as $directory) {
            if (false === $this->createActualDirectory($directory, $connection)) {
                $this->setConnectionRoot();

                return false;
            }

            ftp_chdir($connection, $directory);
        }

        $this->setConnectionRoot();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getMetadata(string $path, Config $config): MetadataInterface
    {
        $connection = $this->getConnection();

        if (@ftp_chdir($connection, $path) === true) {
            $this->setConnectionRoot();

            return new DirectoryMetadata();
        }

        $listing = $this->ftpRawlist('-A', str_replace('*', '\\*', $path));

        if (empty($listing) || in_array('total 0', $listing, true)) {
            throw new FileNotFoundException($path);
        }

        if (preg_match('/.* not found/', $listing[0])) {
            throw new FileNotFoundException($path);
        }

        if (preg_match('/^total [0-9]*$/', $listing[0])) {
            array_shift($listing);
        }

        return $this->normalizeObject($listing[0], '');
    }

    /**
     * @inheritdoc
     */
    public function read(string $path, Config $config): ?array
    {
        if ( ! $object = $this->readStream($path)) {
            return;
        }

        $object['contents'] = stream_get_contents($object['stream']);
        fclose($object['stream']);
        unset($object['stream']);

        return $object;
    }

    /**
     * @inheritdoc
     */
    public function readStream(string $path, Config $config): ?array
    {
        $stream = fopen('php://temp', 'w+b');
        $result = ftp_fget($this->getConnection(), $stream, $path, $this->transferMode);
        rewind($stream);

        if ( ! $result) {
            fclose($stream);

            return;
        }

        return ['stream' => $stream];
    }

    /**
     * @inheritdoc
     */
    public function setVisibility(string $path, string $visibility, Config $config): bool
    {
        $mode = $visibility === AdapterInterface::VISIBILITY_PUBLIC ? $this->getPermPublic() : $this->getPermPrivate();

        return ftp_chmod($this->getConnection(), $mode, $path);
    }

    /**
     * Create a directory.
     *
     * @param string   $directory
     * @param resource $connection
     *
     * @return bool
     */
    protected function createActualDirectory(string $directory, $connection)
    {
        // List the current directory
        $listing = ftp_nlist($connection, '.') ?: [];

        foreach ($listing as $key => $item) {
            if (preg_match('~^\./.*~', $item)) {
                $listing[$key] = substr($item, 2);
            }
        }

        if (in_array($directory, $listing, true)) {
            return true;
        }

        return (bool) ftp_mkdir($connection, $directory);
    }

    /**
     * @inheritdoc
     *
     * @param string $directory
     */
    protected function listDirectoryContents($directory, $recursive = true)
    {
        $directory = str_replace('*', '\\*', $directory);

        if ($recursive && $this->recurseManually) {
            return $this->listDirectoryContentsRecursive($directory);
        }

        $options = $recursive ? '-alnR' : '-aln';
        $listing = $this->ftpRawlist($options, $directory);

        return $listing ? $this->normalizeListing($listing, $directory) : [];
    }

    /**
     * @inheritdoc
     *
     * @param string $directory
     */
    protected function listDirectoryContentsRecursive($directory)
    {
        $listing = $this->normalizeListing($this->ftpRawlist('-aln', $directory) ?: []);
        $output = [];

        foreach ($listing as $directory) {
            $output[] = $directory;
            if ($directory['type'] !== 'dir') continue;

            $output = array_merge($output, $this->listDirectoryContentsRecursive($directory['path']));
        }

        return $output;
    }

    /**
     * Check if the connection is open.
     *
     * @return bool
     * @throws ErrorException
     */
    public function isConnected()
    {
        try {
            return is_resource($this->connection) && ftp_rawlist($this->connection, '/') !== false;
        } catch (ErrorException $e) {
            if (strpos($e->getMessage(), 'ftp_rawlist') === false) {
                throw $e;
            }

            return false;
        }
    }

    /**
     * @return null|string
     */
    protected function isPureFtpdServer()
    {
        $response = ftp_raw($this->connection, 'HELP');

        return stripos(implode(' ', $response), 'Pure-FTPd') !== false;
    }

    /**
     * The ftp_rawlist function with optional escaping.
     *
     * @param string $options
     * @param string $path
     *
     * @return array
     */
    protected function ftpRawlist($options, $path)
    {
        $connection = $this->getConnection();

        if ($this->isPureFtpd) {
            $path = str_replace(' ', '\ ', $path);
        }
        return ftp_rawlist($connection, $options . ' ' . $path);
    }
}
