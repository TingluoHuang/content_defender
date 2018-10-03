<?php
declare(strict_types = 1);
namespace IchHabRecht\ContentDefender\Tests\Functional\Hooks;

/*
 * This file is part of the TYPO3 extension content_defender.
 *
 * (c) Nicole Cordes <typo3@cordes.co>
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

require_once __DIR__ . '/../AbstractFunctionalTestCase.php';

use IchHabRecht\ContentDefender\Tests\Functional\AbstractFunctionalTestCase;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CmdmapDataHandlerHookTest extends AbstractFunctionalTestCase
{
    /**
     * @test
     */
    public function moveCommandMovesRecordInCurrentColPos()
    {
        $dataMap['tt_content'][2] = [
            'colPos' => 3,
        ];

        $commandMap['tt_content'][2] = [
            'move' => -3,
        ];

        $dataHandler = new DataHandler();
        $dataHandler->start($dataMap, $commandMap);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $expressionBuilder = $queryBuilder->expr();
        $count = $queryBuilder->count('uid')
            ->from('tt_content')
            ->where(
                $expressionBuilder->eq(
                    'uid',
                    $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)
                ),
                $expressionBuilder->eq(
                    'colPos',
                    $queryBuilder->createNamedParameter(3, \PDO::PARAM_INT)
                ),
                $expressionBuilder->eq(
                    'sorting',
                    $queryBuilder->createNamedParameter(512, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);

        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function moveCommandMovesAllowedRecordToNewColPos()
    {
        $dataMap['tt_content'][2] = [
            'colPos' => 0,
        ];

        $commandMap['tt_content'][2] = [
            'move' => 2,
        ];

        $dataHandler = new DataHandler();
        $dataHandler->start($dataMap, $commandMap);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $expressionBuilder = $queryBuilder->expr();
        $count = $queryBuilder->count('uid')
            ->from('tt_content')
            ->where(
                $expressionBuilder->eq(
                    'uid',
                    $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)
                ),
                $expressionBuilder->eq(
                    'colPos',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);

        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function moveCommandPreventsDisallowedRecordInNewColPos()
    {
        $dataMap['tt_content'][3] = [
            'colPos' => 0,
        ];

        $commandMap['tt_content'][3] = [
            'move' => 2,
        ];

        $dataHandler = new DataHandler();
        $dataHandler->start($dataMap, $commandMap);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $expressionBuilder = $queryBuilder->expr();
        $count = $queryBuilder->count('uid')
            ->from('tt_content')
            ->where(
                $expressionBuilder->eq(
                    'uid',
                    $queryBuilder->createNamedParameter(3, \PDO::PARAM_INT)
                ),
                $expressionBuilder->eq(
                    'colPos',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);

        $this->assertSame(0, $count);
    }

    /**
     * @test
     */
    public function moveCommandMovesOnlyOneRecordToMaxitemsColPos()
    {
        $commandMap['tt_content'] = [
            2 => [
                'move' => 3,
            ],
            3 => [
                'move' => 3,
            ],
        ];

        $dataHandler = new DataHandler();
        $dataHandler->start([], $commandMap);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $expressionBuilder = $queryBuilder->expr();
        $count = $queryBuilder->count('uid')
            ->from('tt_content')
            ->where(
                $expressionBuilder->eq(
                    'pid',
                    $queryBuilder->createNamedParameter(3, \PDO::PARAM_INT)
                ),
                $expressionBuilder->eq(
                    'colPos',
                    $queryBuilder->createNamedParameter(3, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);

        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function moveCommandMovesRecordToMaxitemsColPosWithoutError()
    {
        $dataMap['tt_content'][5] = [
            'colPos' => 3,
        ];

        $commandMap['tt_content'][5] = [
            'move' => 4,
        ];

        $dataHandler = new DataHandler();
        $dataHandler->start($dataMap, $commandMap);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $expressionBuilder = $queryBuilder->expr();
        $count = $queryBuilder->count('uid')
            ->from('tt_content')
            ->where(
                $expressionBuilder->eq(
                    'uid',
                    $queryBuilder->createNamedParameter(5, \PDO::PARAM_INT)
                ),
                $expressionBuilder->eq(
                    'colPos',
                    $queryBuilder->createNamedParameter(3, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);

        $this->assertSame(1, $count);
        $this->assertNoProcessingErrorsInDataHandler($dataHandler);
    }

    /**
     * @test
     */
    public function copyCommandGeneratesNewRecordInAllowedColPos()
    {
        $commandMap['tt_content'][2] = [
            'copy' => [
                'action' => 'paste',
                'target' => 2,
                'update' => [
                    'colPos' => '0',
                ],
            ],
        ];

        $dataHandler = new DataHandler();
        $dataHandler->start([], $commandMap);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $recordUid = $dataHandler->copyMappingArray['tt_content'][2];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $expressionBuilder = $queryBuilder->expr();
        $count = $queryBuilder->count('uid')
            ->from('tt_content')
            ->where(
                $expressionBuilder->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($recordUid, \PDO::PARAM_INT)
                ),
                $expressionBuilder->eq(
                    'colPos',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);

        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function copyCommandGeneratesNewRecordInAllowedColPosAfterRecord()
    {
        $commandMap['tt_content'][2] = [
            'copy' => -1,
        ];

        $dataHandler = new DataHandler();
        $dataHandler->start([], $commandMap);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $recordUid = $dataHandler->copyMappingArray['tt_content'][2];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $expressionBuilder = $queryBuilder->expr();
        $count = $queryBuilder->count('uid')
            ->from('tt_content')
            ->where(
                $expressionBuilder->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($recordUid, \PDO::PARAM_INT)
                ),
                $expressionBuilder->eq(
                    'colPos',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);

        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function copyCommandPreventsNewRecordInDisallowedColPos()
    {
        $commandMap['tt_content'][3] = [
            'copy' => [
                'action' => 'paste',
                'target' => 2,
                'update' => [
                    'colPos' => '0',
                ],
            ],
        ];

        $dataHandler = new DataHandler();
        $dataHandler->start([], $commandMap);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $this->assertEmpty($dataHandler->copyMappingArray['tt_content'][3]);
    }

    /**
     * @test
     */
    public function copyCommandPreventsNewRecordInDisallowedColPosAfterRecord()
    {
        $commandMap['tt_content'][3] = [
            'copy' => -1,
        ];

        $dataHandler = new DataHandler();
        $dataHandler->start([], $commandMap);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $this->assertEmpty($dataHandler->copyMappingArray['tt_content'][3]);
    }

    /**
     * @test
     */
    public function copyCommandCopiesOnlyOneRecordToMaxitemsColPos()
    {
        $_GET['CB']['paste'] = '|3';
        $commandMap['tt_content'] = [
            2 => [
                'copy' => 3,
            ],
            3 => [
                'copy' => 3,
            ],
        ];

        $dataHandler = new DataHandler();
        $dataHandler->start([], $commandMap);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $expressionBuilder = $queryBuilder->expr();
        $count = $queryBuilder->count('uid')
            ->from('tt_content')
            ->where(
                $expressionBuilder->eq(
                    'pid',
                    $queryBuilder->createNamedParameter(3, \PDO::PARAM_INT)
                ),
                $expressionBuilder->eq(
                    'colPos',
                    $queryBuilder->createNamedParameter(3, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);

        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function copyCommandCopiesAllowedRecordToEmptyMaxitemsColpos()
    {
        $commandMap['tt_content'][4] = [
            'copy' => [
                'action' => 'paste',
                'target' => 3,
                'update' => [
                    'colPos' => '3',
                ],
            ],
        ];

        $dataHandler = new DataHandler();
        $dataHandler->start([], $commandMap);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $recordUid = $dataHandler->copyMappingArray['tt_content'][4];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $expressionBuilder = $queryBuilder->expr();
        $count = $queryBuilder->count('uid')
            ->from('tt_content')
            ->where(
                $expressionBuilder->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($recordUid, \PDO::PARAM_INT)
                ),
                $expressionBuilder->eq(
                    'colPos',
                    $queryBuilder->createNamedParameter(3, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);

        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function deleteCommandDeletesContentElement()
    {
        $commandMap['tt_content'][1] = [
            'delete' => true,
        ];

        $dataHandler = new DataHandler();
        $dataHandler->start([], $commandMap);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $expressionBuilder = $queryBuilder->expr();
        $count = $queryBuilder->count('uid')
            ->from('tt_content')
            ->where(
                $expressionBuilder->eq(
                    'pid',
                    $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)
                ),
                $expressionBuilder->eq(
                    'deleted',
                    $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);

        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function localizeCommandTranslatesRecordInLoadedColumn()
    {
        $commandMap['tt_content'][4] = [
            'localize' => 2,
        ];

        $dataHandler = new DataHandler();
        $dataHandler->start([], $commandMap);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $expressionBuilder = $queryBuilder->expr();
        $count = $queryBuilder->count('uid')
            ->from('tt_content')
            ->where(
                $expressionBuilder->eq(
                    'pid',
                    $queryBuilder->createNamedParameter(3, \PDO::PARAM_INT)
                ),
                $expressionBuilder->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)
                ),
                $expressionBuilder->eq(
                    'colPos',
                    $queryBuilder->createNamedParameter(3, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);

        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function copyToLanguageCommandTranslatesRecordInLoadedColumn()
    {
        $commandMap['tt_content'][4] = [
            'copyToLanguage' => 2,
        ];

        $dataHandler = new DataHandler();
        $dataHandler->start([], $commandMap);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $expressionBuilder = $queryBuilder->expr();
        $count = $queryBuilder->count('uid')
            ->from('tt_content')
            ->where(
                $expressionBuilder->eq(
                    'pid',
                    $queryBuilder->createNamedParameter(3, \PDO::PARAM_INT)
                ),
                $expressionBuilder->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)
                ),
                $expressionBuilder->eq(
                    'colPos',
                    $queryBuilder->createNamedParameter(3, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);

        $this->assertSame(1, $count);
    }
}
