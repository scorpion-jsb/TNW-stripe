<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Stripe\Test\Unit\Gateway\Validator\ResponseValidator;

use TNW\Stripe\Gateway\Helper\SubjectReader;
use TNW\Stripe\Gateway\Validator\ResponseValidator\Authorize;
use Magento\Framework\Phrase;
use Magento\Payment\Gateway\Validator\Result;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Test Authorize
 */
class AuthorizeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Authorize
     */
    private $responseValidator;

    /**
     * @var ResultInterfaceFactory|MockObject
     */
    private $resultInterfaceFactory;

    /**
     * @var SubjectReader|MockObject
     */
    private $subjectReader;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->resultInterfaceFactory = $this->getMockBuilder(ResultInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->subjectReader = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->setMethods(['readResponseObject'])
            ->getMock();

        $this->responseValidator = new Authorize(
            $this->resultInterfaceFactory,
            $this->subjectReader
        );
    }

    /**
     * Run test for validate method
     *
     * @param array $validationSubject
     * @param bool $isValid
     * @param Phrase[] $messages
     * @return void
     *
     * @dataProvider dataProviderTestValidate
     */
    public function testValidate(array $validationSubject, $isValid, $messages)
    {
        /** @var ResultInterface|MockObject $result */
        $result = new Result($isValid, $messages);

        $this->resultInterfaceFactory->method('create')
            ->willReturn($result);

        $this->subjectReader->method('readResponseObject')
            ->with(['response' => ['object' => $validationSubject]])
            ->willReturn($validationSubject);

        $actual = $this->responseValidator->validate(['response' => ['object' => $validationSubject]]);

        self::assertEquals($result, $actual);
    }

    /**
     * @return array
     */
    public function dataProviderTestValidate()
    {
        return [
            [
                [
                    'status' => 'succeeded',
                    'outcome' => ['network_status'=>'approved_by_network']
                ],
                true,
                []
            ],
            [
                [
                    'status' => 'failed',
                    'error' => true,
                    'message' => 'Test error message'
                ],
                false,
                [
                    __('Test error message.'),
                    __('Wrong transaction status')
                ]
            ],
            [
                [
                    'status' => 'succeeded',
                    'outcome' => ['network_status'=>'declined_by_network']
                ],
                'isValid' => false,
                [
                    __('Transaction has been declined'),
                ]
            ],
            [
                [
                    'status' => 'failed',
                    'outcome' => ['network_status'=>'approved_by_network']
                ],
                'isValid' => false,
                [
                    __('Stripe error response.'),
                    __('Wrong transaction status')
                ]
            ],
        ];
    }
}
