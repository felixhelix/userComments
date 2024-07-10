<?php

/**
 * @file UserCommentsSchemaMigration.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UserCommentsSchemaMigration
 * @brief Describe database table structures.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class UserCommentsSchemaMigration extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Capsule::schema()->create('user_comments', function (Blueprint $table) {
            $table->bigInteger('comment_id')->autoIncrement();
            $table->bigInteger('user_id')->nullable();            
            $table->bigInteger('context_id');            
            $table->bigInteger('submission_id')->nullable();
            $table->bigInteger('publication_id')->nullable();            
            $table->bigInteger('publication_version')->nullable();
            $table->bigInteger('foreign_comment_id')->nullable(); // this holds optionally the key of another comment
            $table->datetime('date_created');
            $table->datetime('date_flagged')->nullable()->default(null); // holds the last date flagged
            $table->boolean('flagged')->default(false);
            $table->bigInteger('flagged_by')->nullable();
            $table->boolean('visible')->default(true);
        });

        Capsule::schema()->create('user_comment_settings', function (Blueprint $table) {
            $table->bigInteger('comment_setting_id')->autoIncrement();
            $table->bigInteger('comment_id');
            $table->string('locale', 14)->default('en_US');
            $table->string('setting_name', 255);
            $table->longText('setting_value')->nullable();
            $table->string('setting_type', 6)->comment('(bool|int|float|string|object)');
            // $table->index(['object_id'], 'user_comments_settings_id');
            // $table->unique(['object_id', 'locale', 'setting_name'], 'user_comments_settings_pkey');
        });        
    }

}