
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
const SuccessMessage = 101;//success
const FatalError = -101;//server error
const ParamsRequiredError = -102;//params is required


const OnlineSuccess = 201;
const OfflineSuccess = 301;
const BindSuccess = 401;
const DisBindSuccess = 501;

const OnlineSuccessForManager=601;
const OfflineSuccessForManager=701;
const BindSuccessForManager = 801;
const BindFailRepeatForManager = -801;
const BindFailMisForManager = -802;
const DisBindSuccessForManager = 901;
const DisBindFailMismatchForManager = -901;
const DisBindFailMissForManager = -902;
