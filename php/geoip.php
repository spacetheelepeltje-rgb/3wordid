<?php
/**
 * Country of an IP address, from a local MaxMind DB (.mmdb) file. No external service is
 * asked and no visitor IP leaves the server.
 *
 * Works with these free country databases (same file format):
 *   - DB-IP "IP to Country Lite" (no account needed, CC BY 4.0, monthly update)
 *     https://db-ip.com/db/download/ip-to-country-lite  -> dbip-country-lite-YYYY-MM.mmdb
 *   - MaxMind GeoLite2 Country (free account + licence key)  -> GeoLite2-Country.mmdb
 * Set the path in login/config.php:  define('GEOIP_COUNTRY_DB', '/home/USER/geoip/country.mmdb');
 * See docs/CAR_CHAT.md.
 *
 * Small pure-PHP reader of the MaxMind DB format (spec: https://maxmind.github.io/MaxMind-DB/),
 * so nothing has to be installed with Composer.
 */

class GeoipMmdbReader
{
    private $fh;
    private $nodeCount;
    private $recordSize;
    private $ipVersion;
    private $treeSize;
    private $dataStart;
    private $ipv4Start = null;
    public $metadata;

    public function __construct(string $file)
    {
        $fh = @fopen($file, 'rb');
        if ($fh === false) {
            throw new RuntimeException('cannot open geoip database');
        }
        $this->fh = $fh;
        $size = (int) fstat($fh)['size'];
        $tailLen = min($size, 128 * 1024);
        fseek($fh, $size - $tailLen);
        $tail = (string) fread($fh, $tailLen);
        $marker = "\xAB\xCD\xEFMaxMind.com";
        $pos = strrpos($tail, $marker);
        if ($pos === false) {
            throw new RuntimeException('not a MaxMind DB file');
        }
        $metaStart = $size - $tailLen + $pos + strlen($marker);
        [$meta] = $this->decode($metaStart, $metaStart);
        if (!is_array($meta) || !isset($meta['node_count'], $meta['record_size'])) {
            throw new RuntimeException('bad metadata');
        }
        $this->metadata = $meta;
        $this->nodeCount = (int) $meta['node_count'];
        $this->recordSize = (int) $meta['record_size'];
        $this->ipVersion = (int) ($meta['ip_version'] ?? 6);
        if (!in_array($this->recordSize, [24, 28, 32], true)) {
            throw new RuntimeException('unsupported record size');
        }
        $this->treeSize = (int) (($this->recordSize * 2 / 8) * $this->nodeCount);
        $this->dataStart = $this->treeSize + 16;
    }

    public function __destruct()
    {
        if (is_resource($this->fh)) {
            fclose($this->fh);
        }
    }

    private function read(int $offset, int $len): string
    {
        if ($len === 0) {
            return '';
        }
        fseek($this->fh, $offset);
        $s = fread($this->fh, $len);
        if ($s === false || strlen($s) !== $len) {
            throw new RuntimeException('read past end of database');
        }
        return $s;
    }

    private static function uint(string $bytes): int
    {
        $n = 0;
        for ($i = 0, $l = strlen($bytes); $i < $l; $i++) {
            $n = ($n << 8) | ord($bytes[$i]);
        }
        return $n;
    }

    /** Record $index (0 = left, 1 = right) of a search tree node. */
    private function record(int $node, int $index): int
    {
        switch ($this->recordSize) {
            case 24:
                return self::uint($this->read($node * 6 + $index * 3, 3));
            case 28:
                $b = $this->read($node * 7, 7);
                if ($index === 0) {
                    return ((ord($b[3]) & 0xF0) << 20) | self::uint(substr($b, 0, 3));
                }
                return ((ord($b[3]) & 0x0F) << 24) | self::uint(substr($b, 4, 3));
            default:
                return self::uint($this->read($node * 8 + $index * 4, 4));
        }
    }

    /** The data record for an IP address (array), or null when the database has none. */
    public function get(string $ip): ?array
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return null;
        }
        $bits = strlen($packed) * 8;
        if ($bits === 128 && $this->ipVersion === 4) {
            return null;
        }
        $node = 0;
        if ($bits === 32 && $this->ipVersion === 6) {
            // IPv4 addresses live under ::/96 in an IPv6 tree
            if ($this->ipv4Start === null) {
                $n = 0;
                for ($i = 0; $i < 96 && $n < $this->nodeCount; $i++) {
                    $n = $this->record($n, 0);
                }
                $this->ipv4Start = $n;
            }
            $node = $this->ipv4Start;
        }
        for ($i = 0; $i < $bits && $node < $this->nodeCount; $i++) {
            $bit = (ord($packed[$i >> 3]) >> (7 - ($i & 7))) & 1;
            $node = $this->record($node, $bit);
        }
        if ($node === $this->nodeCount || $node < $this->nodeCount) {
            return null; // no data for this address
        }
        $offset = ($node - $this->nodeCount) - 16 + $this->dataStart;
        [$value] = $this->decode($offset, $this->dataStart);
        return is_array($value) ? $value : null;
    }

    /** Decode one value at $offset. Returns [value, next offset]. $base = start of the data section. */
    private function decode(int $offset, int $base): array
    {
        $ctrl = ord($this->read($offset, 1));
        $offset++;
        $type = $ctrl >> 5;
        if ($type === 1) { // pointer
            $ss = ($ctrl >> 3) & 3;
            $vvv = $ctrl & 7;
            $b = $this->read($offset, $ss + 1);
            $offset += $ss + 1;
            switch ($ss) {
                case 0: $p = ($vvv << 8) | ord($b); break;
                case 1: $p = (($vvv << 16) | self::uint($b)) + 2048; break;
                case 2: $p = (($vvv << 24) | self::uint($b)) + 526336; break;
                default: $p = self::uint($b); break;
            }
            [$value] = $this->decode($base + $p, $base);
            return [$value, $offset];
        }
        if ($type === 0) { // extended type
            $type = 7 + ord($this->read($offset, 1));
            $offset++;
        }
        $size = $ctrl & 0x1F;
        if ($size >= 29) {
            $extra = $size - 28;
            $n = self::uint($this->read($offset, $extra));
            $offset += $extra;
            $size = [1 => 29, 2 => 285, 3 => 65821][$extra] + $n;
        }
        switch ($type) {
            case 2: // utf-8 string
            case 4: // bytes
                return [$this->read($offset, $size), $offset + $size];
            case 3: // double
                return [unpack('E', $this->read($offset, 8))[1], $offset + 8];
            case 15: // float
                return [unpack('G', $this->read($offset, 4))[1], $offset + 4];
            case 5: case 6: case 9: case 10: // unsigned integers
                $b = $this->read($offset, $size);
                $v = $size <= 7 ? self::uint($b) : bin2hex($b);
                return [$v, $offset + $size];
            case 8: // int32
                $v = self::uint($this->read($offset, $size));
                if ($size === 4 && $v >= 0x80000000) { $v -= 0x100000000; }
                return [$v, $offset + $size];
            case 7: // map
                $map = [];
                for ($i = 0; $i < $size; $i++) {
                    [$k, $offset] = $this->decode($offset, $base);
                    [$v, $offset] = $this->decode($offset, $base);
                    $map[(string) $k] = $v;
                }
                return [$map, $offset];
            case 11: // array
                $arr = [];
                for ($i = 0; $i < $size; $i++) {
                    [$v, $offset] = $this->decode($offset, $base);
                    $arr[] = $v;
                }
                return [$arr, $offset];
            case 14: // boolean
                return [$size !== 0, $offset];
            default: // data cache container / end marker: nothing useful
                return [null, $offset];
        }
    }
}

/** Path of the country database from login/config.php, or '' when not set up. */
function geoip_db_path(): string
{
    return defined('GEOIP_COUNTRY_DB') ? (string) GEOIP_COUNTRY_DB : '';
}

/** True when a readable country database is configured. */
function geoip_ready(): bool
{
    $f = geoip_db_path();
    return $f !== '' && is_file($f) && is_readable($f);
}

/**
 * Two-letter country code (upper case) of an IP address, '' when unknown.
 * Throws nothing: a broken or missing file gives ''.
 */
function geoip_country(string $ip): string
{
    static $reader = null;
    if (!geoip_ready()) {
        return '';
    }
    try {
        if ($reader === null) {
            $reader = new GeoipMmdbReader(geoip_db_path());
        }
        $rec = $reader->get($ip);
    } catch (Throwable $e) {
        error_log('geoip: ' . $e->getMessage());
        return '';
    }
    $code = (string) ($rec['country']['iso_code'] ?? ($rec['registered_country']['iso_code'] ?? ''));
    return preg_match('/^[A-Za-z]{2}$/', $code) ? strtoupper($code) : '';
}

/**
 * The visitor's own IP address for the country check. By default REMOTE_ADDR only, because
 * headers like X-Forwarded-For can be typed by anyone. Behind a proxy or Cloudflare, list the
 * proxy addresses in GEOIP_TRUSTED_PROXIES and name the header in GEOIP_CLIENT_IP_HEADER
 * (for example 'HTTP_CF_CONNECTING_IP' or 'HTTP_X_FORWARDED_FOR'); the header is then used
 * only when the request really comes from one of those proxies.
 */
function geoip_client_ip(): string
{
    $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $proxies = defined('GEOIP_TRUSTED_PROXIES') ? (array) GEOIP_TRUSTED_PROXIES : [];
    $header = defined('GEOIP_CLIENT_IP_HEADER') ? (string) GEOIP_CLIENT_IP_HEADER : '';
    if ($header !== '' && $remote !== '' && in_array($remote, $proxies, true) && !empty($_SERVER[$header])) {
        // X-Forwarded-For: the right-most entry was added by the trusted proxy itself
        $parts = array_values(array_filter(array_map('trim', explode(',', (string) $_SERVER[$header]))));
        $ip = $parts ? end($parts) : '';
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    return $remote;
}
