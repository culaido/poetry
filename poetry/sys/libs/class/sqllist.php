<?php

class SQLList
{
    var $pageNum;
    var $sql;
    var $records;
    var $currPage;
    var $totalRows;
    var $currRows;

    function getSql()   { // for debug
        return $this->sql;
    }

    function fetch() { return $this->records->fetch(); }

    function SQLList($param)
    {
        // 參數設定.
        $page   = (isset($param["page"])) ? $param['page'] : 1;
        $size   = (isset($param['size'])) ? $param['size'] : 20;
        $this->sql = trim($param['query']["sql"]);

        $this->sql = "SELECT SQL_CALC_FOUND_ROWS " . substr($this->sql, 6);

        // 讀取 records
        $start = ($page - 1) * $size;
        $limit = ($size != 'all') ? "LIMIT {$start}, {$size}" : "";
	
        $this->sql = "{$this->sql} {$limit}";
	    $this->records = db::select($this->sql, $param['query']['param']);

        if ($size == 'all') return;

	    $this->currRows = $this->records->rowCount();
        $row =  db::fetch('SELECT FOUND_ROWS()', array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => TRUE));
        $row_count = (int) $row['FOUND_ROWS()'];

        // 設定 pageNum.
        $p  = intval(($row_count - 1) / $size);

	    $this->pageNum = ($p < 0) ? 1 : $p+1;

	    // 目前的頁碼.
	    $this->currPage = $page;

	    // 目前的總筆數.
	    $this->totalRows = $row_count;

	    // 讀取 records
        //$start = ($page - 1) * $size;
        //$limit = "LIMIT $start, $size";

        //$sql = "$sql $limit";
	    //$this->records = db_query($sql);

        return $this->records;
    }

/*
    function showIndex($url)
    {
        global $msgPage;
        //echo "<span class='pagelink'>Total: $this->totalRows</span> ";
        for ($i=1; $i<=$this->pageNum; $i++)
        {
            if ($i == $this->currPage)
                echo "<span class='pagecurrent'>$i</span> ";
            else
            {
                $page_url = (strpos($url, "?") === false) ? "<a href='$url?page=$i' class=black>$i</a>" : "<a href='$url&page=$i' class=black>$i</a>";
                echo "<span class='pagelink'>$page_url</span> ";
            }
        }
    }
*/
}



?>