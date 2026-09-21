<?php
namespace Utils;

class TransactionHelper {
    public static function execute(callable $callback) {
        $db = \Core\Database::getInstance()->getConnection();
        
        $inTransaction = $db->inTransaction();
        if (!$inTransaction) {
            $db->beginTransaction();
        }
        
        try {
            $result = $callback($db);
            if (!$inTransaction) {
                $db->commit();
            }
            return $result;
        } catch (\Exception $e) {
            if (!$inTransaction) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
