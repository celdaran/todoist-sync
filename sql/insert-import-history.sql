INSERT INTO import_history (batch,
                            entity,
                            success,
                            inserted,
                            updated,
                            deleted)
VALUES ('$batch',
        '$entity',
        $success,
        $inserted,
        $updated,
        $deleted)
;
