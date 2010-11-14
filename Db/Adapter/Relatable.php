<?php
interface App_Db_Adapter_Relatable
{
	public function describeTableRelations($tableName, $schemaName = null);
}