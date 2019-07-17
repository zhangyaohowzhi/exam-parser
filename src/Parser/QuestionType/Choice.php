<?php

namespace ExamParser\Parser\QuestionType;

use ExamParser\Constants\QuestionElement;
use ExamParser\Parser\Parser;

class Choice extends AbstractQuestion
{
    public function convert($questionLines)
    {
        $question = array(
            'stem' => '',
            'options' => array(),
            'difficulty' => 'normal',
            'score' => 2.0,
            'analysis' => '',
            'answers' => array(),
        );
        if (0 === strpos(trim($questionLines[0]), Parser::CODE_UNCERTAIN_CHOICE_SIGNAL)) {
            $question['type'] = 'uncertain_choice';
            unset($questionLines[0]);
        }
        $answers = array();
        $preNode = QuestionElement::STEM;
        foreach ($questionLines as $line) {
            //处理选项
            if ($this->matchOptions($question, $line, $preNode)) {
                continue;
            }
            //处理答案
            if ($this->matchAnswers($question, $line, $preNode)) {
                continue;
            }
            //处理难度
            if ($this->matchDifficulty($question, $line, $preNode)) {
                continue;
            }
            //处理分数
            if ($this->matchScore($question, $line, $preNode)) {
                continue;
            }

            //处理解析
            if ($this->matchAnalysis($question, $line, $preNode)) {
                continue;
            }

            if (QuestionElement::STEM == $preNode) {
                $question['stem'] .= preg_replace('/^\d{0,5}(\.|、|。|\s)/', '', $line).PHP_EOL;
            }
        }

        return $question;
    }

    protected function matchOptions(&$question, $line, &$preNode)
    {
        $node = 'default';
        if (true === strpos($preNode, '-')) {
            list($node, $index) = explode('-', $preNode);
        }
        if (!$this->hasSignal($line) && QuestionElement::OPTIONS == $node) {
            $question['options'][$index] .= $line;

            return true;
        }
        if (preg_match('/<#([A-Z])#>/', $line, $matches)) {
            $question['options'][ord($matches[1]) - 65] = preg_replace('/<#([A-Z])#>/', '', $line);
            $preNode = QuestionElement::OPTIONS.'-'.(ord($matches[1]) - 65);

            return true;
        }

        return false;
    }

    protected function matchAnswers(&$question, $line, &$preNode)
    {
        if (0 === strpos(trim($line), self::ANSWER_SIGNAL)) {
            preg_match_all('/[A-Z]/', $line, $matches);
            if ($matches) {
                foreach ($matches[0] as $answer) {
                    $answers[] = ord($answer) - 65;
                }
            }
            $question['answers'] = $answers;
            if (empty($question['type'])) {
                if (count($answers) > 1) {
                    $question['type'] = 'choice';
                } else {
                    $question['type'] = 'single_choice';
                }
            }

            $preNode = QuestionElement::ANSWERS;

            return true;
        }

        return false;
    }
}
