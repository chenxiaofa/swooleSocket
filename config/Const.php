
<?php
/**
 * Created by PhpStorm.
 * User: ZZT
 * Date: 18-5-7
 * Time: 下午3:32
 */

const OnlineDeviceToFd = 'screen_online_device_to_fd';
const OnlineFDToDevice = 'screen_online_fd_to_device';


/**
 * 错误码响应messsage
 */
const SuccessMessage = 1001;//success
const FatalError = -1001;//server error
const ParamsRequiredError = -1002;//params is required


const OnlineSuccess = 2001;
const OfflineSuccess = 3001;
const BindSuccess = 4001;
const DisBindSuccess = 5001;

const OnlineSuccessForManager=201;
const OfflineSuccessForManager=301;
const BindSuccessForManager = 401;
const BindFailRepeatForManager = -401;
const BindFailMisForManager = -402;
const DisBindSuccessForManager = 501;
const DisBindFailMismatchForManager = -501;
const DisBindFailMissForManager = -502;
