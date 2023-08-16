<?php

namespace KarsonJo\BookPost {

    use KarsonJo\BookPost\Route\APIRoute;
    use KarsonJo\BookPost\Route\QueryData;
    use KarsonJo\BookPost\SqlQuery\BookQuery;

    BookQuery::firstTimeInit();
    APIRoute::init();
    QueryData::init();
    BookPost::init();

}
