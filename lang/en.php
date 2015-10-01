<?php

return array(
    'CREATE_SUCCESS' => 'Everything has been successfully saved',
    'CREATE_FAIL' => 'An unknown error occurred during saving',
    'ACTIVATE_SUCCESS' => 'Everything has been successfully activated',
    'ACTIVATE_FAIL' => 'An unknown error occurred during activating',
    'DEACTIVATE_SUCCESS' => 'Everything has been successfully deactivated',
    'DEACTIVATE_FAIL' => 'An unknown error occurred during deactivating',
    'UPDATE_SUCCESS' => 'Everything has been successfully updated',
    'UPDATE_FAIL' => 'An unknown error occurred during updating',
    'DELETE_SUCCESS' => 'Everything has been successfully deleted',
    'DELETE_FAIL' => 'An unknown error occurred during erasing',
    'MASSACTION_SUCCESS' => 'All operations were carried out in order',
    'MASSACTION_FAIL' => 'An uknown error occured during mass action',
    'UPLOAD_SUCCESS' => 'Upload has been successfully finished',
    'UPLOAD_FAIL' => 'An uknown error occured during file upload',
    'CHANGES_SAVED' => 'All changes have been successfully saved',

    'COMMON_SUCCESS' => 'Everything went well',
    'COMMON_FAIL' => 'Oops, something went wrong',
    'COMMON_VALIDATION_FAIL' => 'Required fields are not valid',

    'UNKNOW_ERROR' => 'Unknown error eccured',
    'NOT_FOUND' => 'Not found',
    'ACCESS_DENIED' => 'Access denied',
    'LOW_PERMISSIONS' => 'You dont have permissions to do this',

    'PASS_RESET_EMAIL' => 'Password has been reset and sent to email',
    'PASS_RESET' => 'Password has been reset',
    'PASS_EXPIRATION' => 'Password will expire in %s days',
    'PASS_EXPIRATION_TOMORROW' => 'Tomorrow your password will expire',
    'PASS_IN_HISTORY' => 'Password must be different than previous two',
    'PASS_EXPIRED' => 'The password has expired',
    'PASS_WEAK' => 'Password is too weak',
    'PASS_ORIGINAL_NOT_CORRECT' => 'The original password is not correct',
    'PASS_DOESNT_MATCH' => 'Passwords doesnt match',

    'EMAIL_IS_TAKEN' => 'E-mail is already used',
    'EMAIL_SEND_SUCCESS' => 'E-mail was successfully sent',
    'EMAIL_SEND_FAIL' => 'An unknown error occurred during sending email',
    'EMAIL_NO_RECIPIENTS' => 'No recipient is selected',

    'LOGIN_COMMON_ERROR' => 'E-mail or password is not correct',
    'LOGIN_EMAIL_ERROR' => 'E-mail is required',
    'LOGIN_PASS_ERROR' => 'Password is required',
    'LOGIN_TIMEOUT' => 'You has been logged out for long inactivity',
    'LOGOUT_PASS_EXP_CHECK' => 'You havent change your password yer. You will not be able to login.',
    'ACCOUNT_LOCKED' => 'The account is locked',
    'ACCOUNT_INACTIVE' => 'The account has not been activated yet',
    'ACCOUNT_EXPIRED' => 'The account has expired',
    'ACCOUNT_PASS_EXPIRED' => 'The password has expired. For setting new password contanct system administrator.',
    'ACCOUNT_ACTIVATED' => 'The account has been activated',

    'REGISTRATION_SUCCESS' => 'Registration has been successfull',
    'REGISTRATION_SUCCESS_ADMIN_ACTIVATION' => 'Registration has been successfull. You will be notified about account activation via email',
    'REGISTRATION_EMAIL_SUCCESS' => 'Registration has been successfull. Confirm your e-mail address and activate your account',
    'REGISTRATION_EMAIL_FAIL' => 'An unknown error occured during sending e-mail with activation link, please repeat registration later',
    'REGISTRATION_FAIL' => 'Registration failed. Try repeat registration later',

    'ARTICLE_UNIQUE_ID' => 'Article unique id cannot be created. Please create new title.',
    'ARTICLE_TITLE_IS_USED' => 'This title is already used',

    'NO_ROW_SELECTED' => 'No row selected',

    'SYSTEM_DELETE_CACHE' => 'Cache has been successfully deleted',
    'SYSTEM_DB_BACKUP' => 'Database backup has been successfully created',
    'SYSTEM_DB_BACKUP_FAIL' => 'Database backup could not be created',

    //controller customs
    'TITLE_DEFAULT' => 'Sokol',
    'TITLE_DEFAULT_ADMIN' => 'Sokol - Administration',
    'TITLE_ACTION_INDEX' => 'Actions',
    'TITLE_ACTION_ADD' => 'Action - Add',
    'TITLE_ACTION_EDIT' => 'Action - Edit',
    'TITLE_ACTION_COMMENTS' => 'Action - Comments',
    'TITLE_ACTION_ATTEND' => 'Action - Attendance',

    'TITLE_NEWS_INDEX' => 'News',
    'TITLE_NEWS_ADD' => 'News - Add',
    'TITLE_NEWS_EDIT' => 'News - Edit',
    'TITLE_NEWS_COMMENTS' => 'News - Comments',

    'TITLE_REPORT_INDEX' => 'Reports',
    'TITLE_REPORT_ADD' => 'Reports - Add',
    'TITLE_REPORT_EDIT' => 'Reports - Edit',
    'TITLE_REPORT_COMMENTS' => 'Reports - Comments',

    'TITLE_USER_LOGIN' => 'Log In',
    'TITLE_USER_INDEX' => 'Users',
    'TITLE_USER_ADD' => 'User - Add',
    'TITLE_USER_EDIT' => 'User - Edit',
    'TITLE_USER_PROFILE' => 'My Profile',
    
    'ADVERTISEMENT_AVAILABILITY_REQUEST_SUCCESS' => 'Advertisement availability request has been successfully sent',
    'AD_ALREADY_SOLD' => 'Ad which has status sold cannot be changed',
    'AD_PHOTO_NOT_FOUND' => 'No such a photo is connected to the advertisement',
    
    //UPLOAD ERRORS
    'UPLOAD_ERR_INI_SIZE' => 'The uploaded file %s exceeds the upload_max_filesize directive in php.ini',
    'UPLOAD_ERR_FORM_SIZE' => 'The uploaded file %s exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
    'UPLOAD_ERR_PARTIAL' => 'The uploaded file %s was only partially uploaded',
    'UPLOAD_ERR_NO_FILE' => 'No file was uploaded',
    'UPLOAD_ERR_NO_TMP_DIR' => 'Missing a temporary folder',
    'UPLOAD_ERR_CANT_WRITE' => 'Failed to write file %s to disk',
    'UPLOAD_ERR_EXTENSION' => 'File upload stopped by extension',
    'UPLOAD_ERR_DEFAULT' => 'Unknown upload error occured while uploading file %s',
);
