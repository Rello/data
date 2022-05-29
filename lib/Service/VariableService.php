<?php
/**
 * Analytics
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <analytics@scherello.de>
 * @copyright 2019-2022 Marcel Scherello
 */

namespace OCA\Analytics\Service;

use OCA\Analytics\Db\DatasetMapper;
use OCP\IDateTimeFormatter;
use Psr\Log\LoggerInterface;

class VariableService
{
    private $logger;
    private $DatasetMapper;
    private $IDateTimeFormatter;

    public function __construct(
        LoggerInterface $logger,
        DatasetMapper $DatasetMapper,
        IDateTimeFormatter $IDateTimeFormatter
    )
    {
        $this->logger = $logger;
        $this->DatasetMapper = $DatasetMapper;
        $this->IDateTimeFormatter = $IDateTimeFormatter;
    }

    /**
     * replace %*% text variables in name and subheader
     *
     * @param array $datasetMetadata
     * @return array
     */
    public function replaceTextVariables($datasetMetadata)
    {
        $fields = ['name', 'subheader'];
        foreach ($fields as $field) {
            isset($datasetMetadata[$field]) ? $name = $datasetMetadata[$field] : $name = '';

            preg_match_all("/%.*?%/", $name, $matches);
            if (count($matches[0]) > 0) {
                foreach ($matches[0] as $match) {
                    $replace = null;
                    if ($match === '%currentDate%') {
                        $replace = $this->IDateTimeFormatter->formatDate(time(), 'short');
                    } elseif ($match === '%currentTime%') {
                        $replace = $this->IDateTimeFormatter->formatTime(time(), 'short');
                    } elseif ($match === '%lastUpdateDate%') {
                        $timestamp = $this->DatasetMapper->getLastUpdate($datasetMetadata['dataset']);
                        $replace = $this->IDateTimeFormatter->formatDate($timestamp, 'short');
                    } elseif ($match === '%lastUpdateTime%') {
                        $timestamp = $this->DatasetMapper->getLastUpdate($datasetMetadata['dataset']);
                        $replace = $this->IDateTimeFormatter->formatTime($timestamp, 'short');
                    } elseif ($match === '%owner%') {
                        $owner = $this->DatasetMapper->getOwner($datasetMetadata['dataset']);
                        $replace = $owner;
                    }
                    if ($replace !== null) {
                        $datasetMetadata[$field] = preg_replace('/' . $match . '/', $replace, $datasetMetadata[$field]);
                    }
                }
            }
        }
        return $datasetMetadata;
    }

    /**
     * replace variables in filters and apply format
     *
     * @param $reportMetadata
     * @return array
     */
    public function replaceFilterVariables($reportMetadata)
    {
        $filteroptions = json_decode($reportMetadata['filteroptions'], true);
        if (isset($filteroptions['filter'])) {
            foreach ($filteroptions['filter'] as $key => $value) {
                $parsed = $this->parseFilter($value['value'], $value['option']);
                $format = $this->parseFormat($value['value']);

                if (!$parsed) break;
                $filteroptions['filter'][$key]['option'] = $parsed['option'];
                $filteroptions['filter'][$key]['value'] = date($format, $parsed['value']);
            }
        }
        $reportMetadata['filteroptions'] = json_encode($filteroptions);
        return $reportMetadata;
    }

    /**
     * parsing of %*% variables
     *
     * @param $filter
     * @param $option
     * @return array|bool
     */
    private function parseFilter($filter, $option) {
        preg_match_all("/(?<=%).*(?=%)/", $filter, $matches);
        if (count($matches[0]) > 0) {
            $filter = $matches[0][0];
            preg_match('/(last|next|current|to|yester)?/', $filter, $directionMatch);
            preg_match('/[0-9]+/', $filter, $offsetMatch);
            preg_match('/(day|days|week|weeks|month|months|year|years)$/', $filter, $unitMatch);

            if (!$directionMatch[0] || !$unitMatch[0]) {
                return false;
            }

            !$offsetMatch[0] ? $offset = 1: $offset = $offsetMatch[0];

            // remove s to unify e.g. weeks => week
            $unit = rtrim($unitMatch[0], 's');

            if ($directionMatch[0] === "last" || $directionMatch[0] === "yester") {
                $direction = '-';
                //$directionWord = $directionMatch[0];
            } elseif ($directionMatch[0] === "next") {
                $direction = '+';
                //$directionWord = $directionMatch[0];
            } else { // current
                $direction = '+';
                $offset = 0;
                //$directionWord = 'this';
            }

            $timestring = $direction . $offset . ' ' . $unit;
            $baseDate = strtotime($timestring);

            if ($unit === 'day') {
                $startString = 'today';
                //$endString = 'yesterday';
            } else {
                $startString = 'first day of this ' . $unit;
                //$endString = 'last day of ' . $directionWord . ' ' . $unit;
            }
            $startTS = strtotime($startString, $baseDate);
            $start = date("Y-m-d", $startTS);
            //$endTS = strtotime($endString);
            //$end = date("Y-m-d", $endTS);

            $return = [
                'value' => $startTS,
                'option' => 'GT',
                '1$filter' => $filter,
                '2$timestring' => $timestring,
                '3$target' => $baseDate,
                '4$target_clean' => date("Y-m-d", $baseDate),
                '5$startString' => $startString,
                '6$startDate' => $start,
                '7$startTS' => $startTS,
                //'8$endString' => $endString,
                //'9$endDate' => $end,
                //'9$endTS' => $endTS,
           ];
        } else {
            $return = false;
        }
        return $return;
    }

    /**
     * parsing of ( ) format instructions
     *
     * @param $filter
     * @return string
     */
    private function parseFormat($filter) {
        preg_match_all("/(?<=\().*(?=\))/", $filter, $matches);
        if (count($matches[0]) > 0) {
            return $matches[0][0];
        } else {
            return 'Y-m-d H:m:s';
        }
    }
}