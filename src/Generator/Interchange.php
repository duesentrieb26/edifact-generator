<?php

namespace EDI\Generator;

/**
 * Interchange envelope (UNB/UNZ) around one or more composed messages.
 *
 * @package EDI\Generator
 */
class Interchange {
  /** @var string Interchange control reference, sent in UNB and UNZ */
  private $interchangeCode;

  /** @var string Sender identification */
  private $sender;

  /** @var string Syntax identifier, e.g. UNOA or UNOC */
  private $charset = 'UNOA';

  /** @var string Syntax version number */
  private $charsetVersion = '2';

  /** @var string Recipient identification */
  private $receiver;

  /** @var string Date of preparation (YYMMDD) */
  private $date;

  /** @var string Time of preparation (HHMM) */
  private $time;

  /** @var Message[] Composed messages in this interchange */
  private $messages;

  /** @var array|null Finished segment list, set by compose() */
  private $composed;

  /** @var string|int|null Sender identification code qualifier (DE 0007), e.g. 14 for GLN */
  private $senderQualifier;

  /** @var string|int|null Recipient identification code qualifier (DE 0007), e.g. 14 for GLN */
  private $receiverQualifier;


  /**
   * Creates an interchange. Date, time and control reference default to now and a generated id.
   *
   * @param string      $sender          Sender identification, e.g. a GLN
   * @param string      $receiver        Recipient identification, e.g. a GLN
   * @param string|null $date            Date of preparation (YYMMDD); today when null
   * @param string|null $time            Time of preparation (HHMM); now when null
   * @param string|null $interchangeCode Control reference (max 14 characters); generated when null
   */
  public function __construct($sender, $receiver, $date = null, $time = null, $interchangeCode = null) {
    $this->messages = [];

    if ($interchangeCode === null) {
      $this->interchangeCode = 'I' . strtoupper(uniqid());
    } else {
      $this->interchangeCode = $interchangeCode;
    }

    $this->sender = $sender;
    $this->receiver = $receiver;
    if ($date === null) {
      $this->date = date('ymd');
    } else {
      $this->date = $date;
    }
    if ($time === null) {
      $this->time = date('Hi');
    } else {
      $this->time = $time;
    }
  }

  /**
   * Sets the syntax identifier (UNB S001), e.g. UNOA or UNOC.
   *
   * @param string $charset Syntax identifier, e.g. UNOA or UNOC
   *
   * @return $this
   */
  public function setCharset($charset) {
    $this->charset = $charset;
    return $this;
  }

  /**
   * Sets the syntax version number (UNB S001), e.g. 3.
   *
   * @param string $charsetVersion Syntax version number
   *
   * @return $this
   */
  public function setCharsetVersion($charsetVersion) {
    $this->charsetVersion = $charsetVersion;
    return $this;
  }


  /**
   * Adds a message to the interchange. Call the message's compose() before composing the interchange.
   *
   * @param Message $msg Message to send in this interchange
   *
   * @return $this
   */
  public function addMessage($msg) {
    $this->messages[] = $msg;

    return $this;
  }

  /**
   * Builds the UNB and UNZ segments around the segments of all messages.
   *
   * @return $this
   * @throws EdifactException If a message was added without calling its compose()
   */
  public function compose() {
    $temp = [];
    $sender = $this->sender;
    $receiver = $this->receiver;
    if ($this->senderQualifier) {
      $sender = [
        $this->sender,
        $this->senderQualifier,
      ];
    }
    if ($this->receiverQualifier) {
      $receiver = [
        $this->receiver,
        $this->receiverQualifier,
      ];
    }
    $temp[] = [
      'UNB',
      [
        $this->charset,
        $this->charsetVersion,
      ],
      $sender,
      $receiver,
      [
        $this->date,
        $this->time,
      ],
      $this->interchangeCode,
    ];

    foreach ($this->messages as $msg) {
      if ($msg->getComposed() === null) {
        throw new EdifactException(get_class($msg) . ' is not composed, call compose() before adding it to the interchange');
      }
      foreach ($msg->getComposed() as $i) {
        $temp[] = $i;
      }
    }

    $temp[] = [
      'UNZ',
      count($this->messages),
      $this->interchangeCode,
    ];
    $this->composed = $temp;

    return $this;
  }

  /**
   * Returns the composed interchange as a segment array; composes it on the first call.
   *
   * @return array
   * @throws EdifactException If a message was added without calling its compose()
   */
  public function getComposed() {
    if ($this->composed === null) {
      $this->compose();
    }
    return $this->composed;
  }

  /**
   * Returns the sender identification code qualifier (DE 0007).
   *
   * @return string|int|null
   */
  public function getSenderQualifier() {
    return $this->senderQualifier;
  }

  /**
   * Sets the sender identification code qualifier (DE 0007), e.g. 14 for GLN.
   *
   * @param string|int $senderQualifier Identification code qualifier
   *
   * @return $this
   */
  public function setSenderQualifier($senderQualifier) {
    $this->senderQualifier = $senderQualifier;
    return $this;
  }

  /**
   * Returns the recipient identification code qualifier (DE 0007).
   *
   * @return string|int|null
   */
  public function getReceiverQualifier() {
    return $this->receiverQualifier;
  }

  /**
   * Sets the recipient identification code qualifier (DE 0007), e.g. 14 for GLN.
   *
   * @param string|int $receiverQualifier Identification code qualifier
   *
   * @return $this
   */
  public function setReceiverQualifier($receiverQualifier) {
    $this->receiverQualifier = $receiverQualifier;
    return $this;
  }
}
