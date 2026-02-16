<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 🚫 Bad Words (Direct Abuse → Block Message)
    |--------------------------------------------------------------------------
    | শুধুমাত্র সরাসরি অপমান/ইনসাল্ট থাকবে
    */

    'bad_words' => [
        'faltu',
        'baje',
        'joghonno',
        'osovvo',
        'beyadob',
        'useless page',
        'stupid page',
    ],


    /*
    |--------------------------------------------------------------------------
    | 😡 Angry Words (Complaint / Frustration → Seller Alert Only)
    |--------------------------------------------------------------------------
    | এগুলো ব্লক না করে শুধু অ্যালার্ট দিবে
    */

    'angry_words' => [

        // 🔴 Delivery / Shipping
        'parcel koi',
        'order kothay',
        'kothay amar parcel',
        'eto deri keno',
        'late keno',
        'delay keno',
        'delivery hoy nai',
        'delivery hocche na',
        'tracking vul',
        'tracking kaj korche na',
        'ekhono ashe nai',
        'onek din hoye gelo',

        // 🔴 Payment / Refund
        'refund koi',
        'amar taka ferot din',
        'payment niye chup',
        'bkash korechi response nai',
        'double taka kata',

        // 🔴 Product Issue
        'product mil nai',
        'chobi ar product alada',
        'quality kharap',
        'damage product',
        'vanga asche',
        'wrong product',
        'size vul',
        'color vul',
        'original na',
        'copy product',

        // 🔴 Communication
        'reply den na keno',
        'seen kore rakhsen',
        'ignore koren keno',
        'support koi',
        'phone dhoren na',

        // 🔴 Frustration
        'order cancel koren',
        'ar lagbe na',
        'ar order dibo na',
        'last time order',
        'khub disappointed',
        'very disappointed',
        'worst service',
        'bad service',
    ],


    /*
    |--------------------------------------------------------------------------
    | ⚖️ Threat Words (High Priority Alert)
    |--------------------------------------------------------------------------
    | Legal / Reputation Threat → Immediate Seller Notification
    */

    'threat_words' => [
        'case korbo',
        'consumer court e jabo',
        'report korbo',
        'page report korbo',
        'police e complain korbo',
        'viral kore dibo',
        'review kharap dibo',
    ],


    /*
    |--------------------------------------------------------------------------
    | 🔄 Loop Settings
    |--------------------------------------------------------------------------
    */

    'max_repeats' => 3, // একই মেসেজ কতবার দিলে আটকাবে

];
