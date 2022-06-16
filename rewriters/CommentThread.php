<?php
namespace Rewriter;

use function YukisCoffee\getPropertyAtPath as getProp;

class CommentThread
{
    // Excepted structure is very similar to the InnerTube
    // comments next response, so this is very basic.
    const ACTIONS_PATH = "actionButtons.commentActionButtonsRenderer";
    const LIKE_BUTTON_PATH = self::ACTIONS_PATH . ".likeButton.toggleButtonRenderer";
    const DISLIKE_BUTTON_PATH = self::ACTIONS_PATH . ".dislikeButton.toggleButtonRenderer";
    const COMMON_A11Y_LABEL = "accessibilityData.label";

    public static function bakeComments($context)
    {
        // Top-level function
        // $context = continuation command

        $context = @$context->continuationItems;
        
        $out = ["commentsThreads" => []];
        
        if ($context) for ($i = 0, $count = count($context); $i < $count; $i++) {
            if (isset($context[$i]->commentThreadRenderer))
            {
                $out["commentThreads"][] = self::commentThreadRenderer($context[$i]->commentThreadRenderer);
            }
            else if ($count - 1 == $i && isset($context[$i]->continuationItemRenderer))
            {
                $out += ["commentContinuationRenderer" => self::commentContinuationRenderer($context[$i]->continuationItemRenderer)];
            }
        }
        
        return $out;
    }

    public static function bakeReplies($context)
    {
        // Top level function
        // $context = (Array containing all commentRenderer items)

        $items = @$context->continuationItems;

        $out = ["comments" => [], "repliesTargetId" => str_replace("comment-replies-item-", "", $context->targetId)];

		if ($items) for ($i = 0, $count = count($items); $i < $count; $i++)
        {
            if (isset($items[$i]->commentRenderer))
            {
                $out["comments"][] = self::commentRenderer($items[$i]->commentRenderer, true);
            }
            else if ($count -1 == $i && isset($items[$i]->continuationItemRenderer))
            {
                $out += ["repliesContinuationRenderer" => self::repliesContinuationRenderer($items[$i]->continuationItemRenderer)];
            }
        }

        return $out;
    }
    
    public static function commentThreadRenderer($context)
    {
        $out = [];

        // PLEASE NOTE:
        // The extra preceding property "comments"/"replies" is removed by this.
        if (isset($context->comment)) {
            $out['commentRenderer'] = self::commentRenderer($context->comment->commentRenderer);
        }

        if (isset($context->replies)) {
            $out['commentRepliesRenderer'] = self::commentRepliesRenderer($context->replies->commentRepliesRenderer);
        }
        
        return ['commentThreadRenderer' => $out];
    }
    
    public static function commentRenderer($context, $isReply = false)
    {
        // Right now, the method is to modify a
        // standard InnerTube response.

        $context->isReply = $isReply;
        if (isset($context->voteCount)) self::addLikeCount($context);

        return $context;
    }

    public static function commentRepliesRenderer($context)
    {
        return $context;
    }

    public static function commentContinuationRenderer($context)
    {
        return $context->continuationEndpoint->continuationCommand;
    }

    public static function repliesContinuationRenderer($context)
    {
        $context = $context->button->buttonRenderer;
        return
            [
                "token" => $context->command->continuationCommand->token,
                "text" => $context->text
            ];
    }
    
    public static function addLikeCount(&$context)
    {
        // Adds to context:
        /*
         {
            "voteCount": {
                "indifferentText": "2" | (string) Spliced count from accessibility label
                "likedText": "3" | (string) => (int)++indifferentText
            }
         }
        */

        $likeAriaLabel = getProp($context, 
            self::LIKE_BUTTON_PATH .
            ".accessibilityData." .
            self::COMMON_A11Y_LABEL
        );
        
        $count = (int)self::getLikeCountFromLabel($likeAriaLabel);

        $context->voteCount =
            [
                "indifferentText" => (string)$count,
                "likedText" => (string)++$count
            ];
    }

    /**
     * TODO (kirasicecreamm): i18n
     */
    public static function getLikeCountFromLabel($label)
    {
        return preg_replace("/(Like this )|(comment)|(reply)|( along with )|(,)|( other person)|(other people)/", "", $label);
    }
}