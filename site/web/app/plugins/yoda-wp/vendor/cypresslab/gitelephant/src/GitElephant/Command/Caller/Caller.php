<?php
/**
 * GitElephant - An abstraction layer for git written in PHP
 * Copyright (C) 2013  Matteo Giachino
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see [http://www.gnu.org/licenses/].
 */

namespace GitElephant\Command\Caller;

use GitElephant\Exception\InvalidRepositoryPathException;
use \GitElephant\GitBinary;
use \Symfony\Component\Process\Process;

/**
 * Caller
 *
 * @author Matteo Giachino <matteog@gmail.com>
 */
class Caller implements CallerInterface
{
    /**
     * GitBinary instance
     *
     * @var \GitElephant\GitBinary
     */
    private $binary;

    /**
     * the repository path
     *
     * @var string
     */
    private $repositoryPath;

    /**
     * the output lines of the command
     *
     * @var array
     */
    private $outputLines = array();

    /**
     * raw output
     *
     * @var string
     */
    private $rawOutput;

    /**
     * the output lines of the command
     *
     * @var array
     */
    private $errorLines = array();

    /**
     * raw output
     *
     * @var string
     */
    private $rawErrorOutput;

    /**
     * Class constructor
     *
     * @param \GitElephant\GitBinary $binary         the binary
     * @param string                 $repositoryPath the physical base path for the repository
     */
    public function __construct(GitBinary $binary, $repositoryPath)
    {
        $this->binary         = $binary;

        if (!is_dir($repositoryPath)) {
            throw new InvalidRepositoryPathException($repositoryPath);
        }

        $this->repositoryPath = $repositoryPath;
    }

    /**
     * Get the binary path
     *
     * @return mixed
     */
    public function getBinaryPath()
    {
        return $this->binary->getPath();
    }

    /**
     * Executes a command
     *
     * @param string $cmd               the command to execute
     * @param bool   $git               if the command is git or a generic command
     * @param null   $cwd               the directory where the command must be executed
     * @param array  $acceptedExitCodes exit codes accepted to consider the command execution successful
     *
     * @throws \RuntimeException
     * @throws \Symfony\Component\Process\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Process\Exception\ProcessTimedOutException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @return Caller
     */
    public function execute($cmd, $git = true, $cwd = null, $acceptedExitCodes = array(0))
    {
        if ($git) {
            $cmd = $this->binary->getPath() . ' ' . $cmd;
        }

        if (stripos(PHP_OS, 'WIN') !== 0) {
            // We rely on the C locale in all output we parse.
            $cmd = 'LC_ALL=C ' . $cmd;
        }

        $process = new Process($cmd, is_null($cwd) ? $this->repositoryPath : $cwd);
        $process->setTimeout(15000);
        $process->run();
        if (!in_array($process->getExitCode(), $acceptedExitCodes)) {
            $text = 'Exit code: ' . $process->getExitCode();
            $text .= ' while executing: "' . $cmd;
            $text .= '" with reason: ' . $process->getErrorOutput();
            $text .= "\n" . $process->getOutput();
            throw new \RuntimeException($text);
        }
        $this->rawOutput = $process->getOutput();

        // rtrim values
        $values = array_map('rtrim', explode(PHP_EOL, $process->getOutput()));
        $this->outputLines = $values;

        return $this;
    }

    /**
     * returns the output of the last executed command
     *
     * @return string
     */
    public function getOutput()
    {
        return implode("\n", $this->outputLines);
    }

    /**
     * returns the output of the last executed command as an array of lines
     *
     * @param bool $stripBlankLines remove the blank lines
     *
     * @return array
     */
    public function getOutputLines($stripBlankLines = false)
    {
        if ($stripBlankLines) {
            $output = array();
            foreach ($this->outputLines as $line) {
                if ('' !== $line) {
                    $output[] = $line;
                }
            }

            return $output;
        }

        return $this->outputLines;
    }

    /**
     * Get RawOutput
     *
     * @return string
     */
    public function getRawOutput()
    {
        return $this->rawOutput;
    }

    /**
     * returns the error output of the last executed command
     *
     * @return string
     */
    public function getErrorOutput()
    {
        return implode("\n", $this->errorLines);
    }

    /**
     * returns the error output of the last executed command as an array of lines
     *
     * @param bool $stripBlankLines remove the blank lines
     *
     * @return array
     */
    public function getErrorLines($stripBlankLines = false)
    {
        if ($stripBlankLines) {
            $output = array();
            foreach ($this->errorLines as $line) {
                if ('' !== $line) {
                    $output[] = $line;
                }
            }

            return $output;
        }

        return $this->errorLines;
    }

    /**
     * Get RawErrorOutput
     *
     * @return string
     */
    public function getRawErrorOutput()
    {
        return $this->rawErrorOutput;
    }
}