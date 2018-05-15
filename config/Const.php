
<?php
/**
 * Created by PhpStorm.
 * User: ZZT
 * Date: 18-5-7
 * Time: 下午3:32
 */

const OnlineDeviceToFd = 'zhy_online_device_to_fd';
const OnlineFDToDevice = 'zhy_online_fd_to_device';
const OnlineMeeting = 'zhy_meeting';


/**
 * 错误码响应messsage
 */
const SuccessMessage = 1001;//success
const FatalError = -1001;//server error
const ParamsRequiredError = -1002;//params is required

const MeetingCreateSuccess = 2001;
const NoMeetingError = -2001;//no meeting
const NotManagerError = -2002;//非主持人
const QuitMeetingSuccess = 2003;
const PromiseQuitMeetingSuccess=2004;
const DissolveMeetingSuccess=2005;
const FlushMeetingMembersSuccess = 2001;//创建会议成功
const RepeatMessageSuccess = 3001;//作为转发服务器，发送消息
